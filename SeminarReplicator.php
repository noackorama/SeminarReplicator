<?php

class SeminarReplicator extends StudipPlugin implements SystemPlugin {


    function __construct()
    {
        parent::__construct();
        if (is_object($GLOBALS['perm'])
        && $GLOBALS['perm']->have_perm('admin')) {
            $navigation = new Navigation(_("Veranstaltungs-Vervielfältiger"), PluginEngine::getUrl($this,array(),'index'));
            Navigation::insertItem('/start/replicator', $navigation, 'search');
        }
    }


    function index_action()
    {
        if (!$GLOBALS['perm']->have_perm("admin")) {
            throw new AccessDeniedException(_("Sie sind nicht berechtigt, dieses Plugin zu benutzen."));
        }
        $db = DBManager::get();
        if( Request::submitted('do_search_source')){
            $result = search_range(Request::quoted('search_source'));

            if (is_array($result)){
                $result = array_filter($result, function($r) {return $r["type"] == "sem";});
                if (count($result)){
                    PageLayout::postMessage(MessageBox::success(sprintf(_("Ihre Sucher ergab %s Treffer."), count($result))));
                    $show_source_result = true;
                }
            } else {
                PageLayout::postMessage(MessageBox::info( _("Ihre Suche ergab keine Treffer.")));
            }
        }
        if (Request::submitted('do_choose_source')){
            $source_id = Request::option('search_source_result');
            $copy_count = 5;
            Request::set('to_copy', null);
        } else {
            if (Request::submitted('source_id')) {
                $source_id = Request::option('source_id');
                $copy_count = Request::int('copy_count');
                if ($copy_count < 1) $copy_count = 1;
            }
        }

        if($source_id){
            $source = Seminar::getInstance($source_id);
            $source_name = $source->getName() . ' ('.$source->getStartSemesterName().')';
            $copy_type = Request::int('copy_type', $source->status);
            if (SeminarCategories::getByTypeId($copy_type)->course_creation_forbidden) {
                $copy_type = 0;
            }
            if (SeminarCategories::getByTypeId($source->status)->only_inst_user) {
                $search_template = "user_inst";
            } else {
                $search_template = "user";
            }
            $bet_inst = $db->query("SELECT institut_id FROM seminar_inst WHERE seminar_id=" . $db->quote($source_id))->fetchAll(PDO::FETCH_COLUMN);
            $source_dozenten = array_keys($source->getMembers('dozent'));
            if ($copy_count) {
                $r = Request::getArray('to_copy');
                $delete_lecturer = Request::getArray('delete_lecturer');
                $add_lecturer = count(Request::getArray('add_lecturer')) ? (int)key(Request::getArray('add_lecturer')) : null;
                for ($i = 0; $i < $copy_count; $i++) {
                    $to_copy['nr'][$i] = isset($r['nr'][$i]) ? $r['nr'][$i] : $source->getNumber();
                    $to_copy['name'][$i] = isset($r['name'][$i]) ? $r['name'][$i] : $source->getName();
                    $to_copy['participants'][$i] = isset($r['participants'][$i]) ? 1 : 0;
                    $to_copy['lecturers'][$i] = $r['lecturers'][$i];
                    if (empty($to_copy['lecturers'][$i])) {
                        $to_copy['lecturers'][$i] = $source_dozenten;
                    } else {
                        if (isset($delete_lecturer[$i]) && count($to_copy['lecturers'][$i]) > 1) {
                            $to_delete = array_search(key($delete_lecturer[$i]),$to_copy['lecturers'][$i]);
                            if ($to_delete !== false) {
                                unset($to_copy['lecturers'][$i][$to_delete]);
                            }
                        }
                    }
                    if ($add_lecturer === $i) {
                        $to_copy['lecturers'][$i][] = Request::option('add_doz_' . $add_lecturer);
                    }
                    $to_copy['search_lecturer'][$i] = new PermissionSearch($search_template,
                                       sprintf(_("%s auswählen"), get_title_for_status('dozent', 1, $source->status)),
                                       "user_id",
                                       array('permission' => 'dozent',
                                             'exclude_user' => $to_copy['lecturers'][$i],
                                             'institute' => $bet_inst
                                          )
                                       );
                }
            }
            if (Request::submitted('do_copy') && count($to_copy)) {
                $copied = array();
                $lecturer_insert = $db->prepare("INSERT INTO seminar_user (seminar_id,user_id,status,position,gruppe,admission_studiengang_id,comment,visible,mkdate) VALUES (?,?,'dozent',?,?,'','','yes',UNIX_TIMESTAMP())");
                $copy_seminar_inst = $db->prepare("INSERT INTO seminar_inst (seminar_id,institut_id) SELECT ?,institut_id FROM seminar_inst WHERE seminar_id=?");
                $copy_seminar_sem_tree = $db->prepare("INSERT INTO seminar_sem_tree (seminar_id,sem_tree_id) SELECT ?,sem_tree_id FROM seminar_sem_tree WHERE seminar_id=?");
                $copy_seminar_user = $db->prepare("INSERT IGNORE INTO seminar_user (seminar_id,user_id,status,gruppe,admission_studiengang_id, mkdate,comment,position) SELECT ?,user_id,status,gruppe,admission_studiengang_id,UNIX_TIMESTAMP(),'',0 FROM seminar_user WHERE status IN ('user','autor','tutor') AND seminar_id=?");
                $copy_seminar_userdomains = $db->prepare("INSERT INTO seminar_userdomains (seminar_id,userdomain_id) SELECT ?,userdomain_id FROM seminar_userdomains WHERE seminar_id=?");
                $copy_admission_seminar_studiengang = $db->prepare("INSERT INTO admission_seminar_studiengang (seminar_id,studiengang_id,quota) SELECT ?,studiengang_id,quota FROM admission_seminar_studiengang WHERE seminar_id=?");
                $copy_statusgruppen = $db->prepare("INSERT INTO statusgruppen (statusgruppe_id,name,range_id,position,size,selfassign,mkdate) SELECT MD5(CONCAT(statusgruppe_id, ?)),name,?,position,size,selfassign,UNIX_TIMESTAMP() FROM statusgruppen WHERE range_id=?");
                $copy_statusgruppe_user = $db->prepare("INSERT INTO statusgruppe_user (statusgruppe_id,user_id,position) SELECT MD5(CONCAT(statusgruppe_user.statusgruppe_id, ?)),user_id,statusgruppe_user.position FROM statusgruppen INNER JOIN statusgruppe_user USING(statusgruppe_id) WHERE range_id=?");

                for ($i = 0; $i < $copy_count; $i++) {
                    $new_sem = clone $source;
                    $new_sem_id = md5($source->createID());
                    $new_sem->id = $new_sem_id;
                    $new_sem->is_new = true;
                    $new_sem->status = Request::int('copy_type', 1);
                    $new_sem->irregularSingleDates = null;
                    $new_sem->issues = null;
                    $new_sem->metadate = new Metadate();
                    $new_sem->members = null;
                    $new_sem->name = $to_copy['name'][$i];
                    $new_sem->seminar_number = $to_copy['nr'][$i];
                    if ($new_sem->store()) {
                        log_event("SEM_CREATE", $new_sem_id);
                        $gruppe = (int)select_group($new_sem->getSemesterStartTime());
                        $position = 1;
                        foreach($to_copy['lecturers'][$i] as $lecturer) {
                            $lecturer_insert->execute(array($new_sem_id, $lecturer,$position, $gruppe));
                        }
                        $copy_seminar_inst->execute(array($new_sem_id, $source_id));
                        $copy_seminar_sem_tree->execute(array($new_sem_id, $source_id));
                        $copy_seminar_userdomains->execute(array($new_sem_id, $source_id));
                        $copy_admission_seminar_studiengang->execute(array($new_sem_id, $source_id));
                        if ($to_copy['participants'][$i]) {
                            $copy_seminar_user->execute(array($new_sem_id, $source_id));
                            $copy_statusgruppen->execute(array($new_sem_id,$new_sem_id, $source_id));
                            $copy_statusgruppe_user->execute(array($new_sem_id, $source_id));
                        }
                        $copied[] = $new_sem;
                    }
                }
                PageLayout::postMessage(MessageBox::success(sprintf(_("Es wurden %s Kopien erstellt."), count($copied))));
                $source_id = null;
            }
        }


        PageLayout::setTitle(_("Veranstaltungs-Vervielfältiger"));
        $template_factory = new Flexi_TemplateFactory(dirname(__file__)."/templates");
        $template = $template_factory->open('index.php');
        $template->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox.php'));
        echo $template->render(compact('source_id', 'source_name', 'show_source_result', 'result', 'copy_count','copy_type', 'to_copy', 'copied'));
    }
}
