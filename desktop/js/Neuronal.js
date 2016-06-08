$('body').on( 'click','.bt_selectCmdExpression', function() {
	var _this=this;
	$(this).value()
	jeedom.cmd.getSelectModal({cmd: {type: ''},eqLogic: {eqType_name : ''}}, function (result) {
		$(_this).closest('td').find('.eqLogicAttr[data-l1key=configuration][data-l2key=ES_Neurone]').val(result.human);
	});
});  
$('body').on('click','.bt_add',function(){
	var tr =$('<tr>');
  	tr.append($('<td>')
  		.append($('<input class="eqLogicAttr form-control input-sm " data-l1key="configuration" data-l2key="ES_Neurone" data-l3key="E" style="width : 90%;display : inline-block;margin:5px;">'))
  		.append($('<a style="display : inline-block;margin:5px;" class="btn btn-default btn-xs cursor bt_selectCmdExpression" title="Rechercher une commande">')
			.append($('<i class="fa fa-list-alt">'))));
  	tr.append($('<td>')
  		.append($('<a style="display : inline-block;margin:5px;" class="btn btn-success btn-xs cursor bt_add" title="Ajouter une commande">')
			.append($('<i class="fa fa-plus-circle">'))));
  	tr.append($('<td>')
  		.append($('<a style="display : inline-block;margin:5px;" class="btn btn-danger btn-xs cursor bt_del" title="Supprimer une commande">')
			.append($('<i class="fa fa-list-alt">'))));

	$(this).closest('table').append(tr);
});
$('body').on('click','.bt_del',function(){
	$(this).closest('tr').remove();
});
$("#table_cmd_Entree").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd_Sorite").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
function addCmdToTable(_cmd) {

	if (!isset(_cmd)) {
        var _cmd = {};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
}
