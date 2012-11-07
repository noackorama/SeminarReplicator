<h1>Studierendenübersicht</h1>
<div>
<form action="?download=1" onSubmit="jQuery('#download_filter').val(jQuery('#overview-table_filter').find('input').val());">
<button type="submit"> - download - </button>
<input type="hidden" name="download_filter" id="download_filter" value="">
</form>
</div>
<table class="default" id="overview-table">
<thead>
<tr>
<th>Studiengruppe</th>
<th>Vorname</th>
<th>Nachname</th>
<th>Nutzername</th>
</tr>
</thead>
<tbody>
<? foreach ($data as $r) : ?>
<tr class="cycle_odd">
<td><a href="<?=UrlHelper::getLink('teilnehmer.php', array('cid' => $r['seminar_id']))?>"><?=htmlready($r['studiengruppe'])?></a></td>
<td><?=htmlready($r['vorname'])?></td>
<td><?=htmlready($r['nachname'])?></td>
<td><a href="<?=UrlHelper::getLink('dispatch.php/admin/user/edit/' . $r['user_id'])?>"><?=htmlready($r['username'])?></a></td>
</tr>
<? endforeach; ?>
</tbody>
</table>
<script>
jQuery(document).ready(function() {
        jQuery('#overview-table').dataTable({
                "oSearch": {"sSearch": "", "bSmart" : false},
                "iDisplayLength": 25,
                 "oLanguage": {
            "sLengthMenu": "Zeige _MENU_ Einträge",
            "sZeroRecords": "Nichts gefunden",
            "sInfo": "Zeige _START_ bis _END_ von _TOTAL_ Einträgen",
            "sInfoEmpty": "Zeige 0 bis 0 von 0 Einträgen",
            "sInfoFiltered": "(gefiltert von insgesamt _MAX_ Einträgen)",
            "sSearch": "Einträge filtern",
            "oPaginate": {
                "sNext": "weiter >>",
                "sPrevious": "<< zurück "
            }

        }
        });
} );
</script>