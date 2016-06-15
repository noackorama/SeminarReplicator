<form method="post" action="<?=UrlHelper::getLink('', array('cid' => null))?>">
<h2><?php echo _("Quelle:"). '&nbsp;' . htmlready($source_name)?>
<?php if($source_id):?>
    <a title="<?php echo _("Zur Quellveranstaltung springen")?>" href="<?php echo UrlHelper::getLink('seminar_main.php', array('cid' => $source_id))?>">
    <?php echo Assets::img('icons/16/blue/link-intern.png')?>
    </a>
<?php endif?>
</h2>
<?php if($show_source_result):?>
    <label style="padding-right:10px;width:100px;display:block;float:left;" for="search_source_result">
    <?php echo _("Quelle wählen:")?>
    </label>
    <select name="search_source_result" id="search_source_result" >
    <?php foreach($result as $s_id => $data):?>
        <option value="<?php echo $s_id?>" <?php echo $source_id == $s_id ? 'selected' : ''?>>
        <?php echo htmlready($data['name'])?>
        </option>
    <?php endforeach?>
    </select>
    <?=Assets::input('icons/16/blue/refresh.png', array('name' => 'do_search_cancel'))?>
    <?php echo Studip\Button::createAccept(_('Auswählen'), 'do_choose_source')?>
<?php else:?>
    <label style="padding-right:10px;width:100px;display:block;float:left;" for="search_destination">
    <?php echo _("Quelle suchen:")?>
    </label>
    <input type="text" id="search_source" name="search_source" size="40">
    <?=Assets::input('icons/16/blue/search.png', array('name' => 'do_search_source'))?>
<?php endif?>
<br><br>
<?php if($source_id):?>
<input type="hidden" name="source_id" value="<?=htmlReady($source_id)?>">
<label style="padding-right:10px;width:100px;display:block;float:left;" for="copy_count">
    <?php echo _("Anzahl Kopien:")?>
    </label>
    <input type="text" id="copy_count" name="copy_count" size="5" value="<?=$copy_count?>">

    <?php echo Studip\Button::createAccept(_('Übernehmen'), 'do_choose_count') ?>
    <br>
    <br>
<label style="padding-right:10px;width:100px;display:block;float:left;" for="copy_type">
    <?php echo _("Typ:");?>
</label>
<select name="copy_type">
<?
foreach (SeminarCategories::getAll() as $sc) {
    foreach ($sc->getTypes() as $key => $value) {
        if (!$sc->course_creation_forbidden) {
            ?>
            <option value="<?=$key?>" <?=($copy_type == $key ? 'selected' : '')?>>
            <?=htmlReady($value . ' (' . $sc->name . ')')?>
            </option>
            <?
        }
    }
}
?>
</select>
<? if ($copy_type === 0) {
    echo '&lArr; ' . _("Mit dem ursprünglichen Typ dürfen keine Veranstaltungen angelegt werden. Wählen Sie ggf. einen alternativen Typ!");
}
?>
    <br>
    <ol>
    <?for ($i = 0; $i < $copy_count;$i++) : ?>
        <li>
        <div style="border: 1px dotted; padding:3px;margin-top:5px;width:90%;background-color:lightgrey">
        <input type="text" name="to_copy[nr][<?=$i?>]" size="10" value="<?=htmlReady($to_copy['nr'][$i])?>">
        <input type="text" name="to_copy[name][<?=$i?>]" size="50" value="<?=htmlReady($to_copy['name'][$i])?>">
        <br>
        <b>Teilnehmer kopieren?</b> <input type="checkbox" style="vertical-align:middle" name="to_copy[participants][<?=$i?>]" value="1" <?=($to_copy['participants'][$i] ? 'checked' : '')?>>
        <br>
        <b>Dozenten: </b>
        <br>
        <? foreach($to_copy['lecturers'][$i] as $lecturer) : ?>
            <?=htmlReady(get_fullname($lecturer))?>
            <input type="hidden" name="to_copy[lecturers][<?=$i?>][]" value="<?=$lecturer?>">
            <?=Assets::input('icons/16/black/trash.png', array('name' => 'delete_lecturer['.$i.']['.$lecturer.']'))?>
            <br>
        <? endforeach;?>
            <div style="float:left">
                <?=Assets::input('icons/16/yellow/arr_2up.png', array('name' => 'add_lecturer['.$i.']'));?>
            </div>
<? echo QuickSearch::get("add_doz_" . $i, $to_copy['search_lecturer'][$i])
                            ->withButton(array('search_button_name' => 'search_doz', 'reset_button_name' => 'reset_search'))
                            ->render();
                            ?>

        </div>
        </li>
    <?endfor;?>
    </ol>
    <div style="text-align:center">
    <?php echo Studip\Button::createAccept(_('Kopieren'),'do_copy')?>
    <?php echo Studip\LinkButton::createCancel(_('Abbrechen'), '?')?>
    </div>
<?php endif?>
<?php if(count($copied)):?>
<h3>Erstellte Kopien</h3>
<ol>
    <?foreach ($copied as $copy) : ?>
        <li>
        <a href="<?=UrlHelper::getLink('seminar_main.php?auswahl=' . $copy->getId())?>"><?=htmlReady(trim($copy->veranstaltungsnummer . ' ' . $copy->name))?></a>
        <br>
        <?
        echo join('; ', $copy->members->findBy('status', 'dozent')->pluck('nachname'));
        ?>
        <br>
        </li>
    <?endforeach;?>
</ol>
<?php endif?>
</form>
