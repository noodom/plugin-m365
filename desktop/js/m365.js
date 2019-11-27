$("body").delegate('.bt_removeAction', 'click', function () {
    console.log('remove action');
    var type = $(this).attr('data-type');
    $(this).closest('.' + type).remove();
});

$('.addAction').on('click', function () {
    console.log('ok');
    addAction({}, $(this).attr('data-type'));
});

$(".eqLogic").delegate(".listCmdInfo", 'click', function () {
    console.log('click list cmd info');
    var el = $(this).closest('.form-group').find('.eqLogicAttr');
    jeedom.cmd.getSelectModal({
        cmd: {
            type: 'info'
        }
    }, function (result) {
        if (el.attr('data-concat') == 1) {
            el.atCaret('insert', result.human);
        } else {
            el.value(result.human);
        }
    });
});


$("body").delegate(".listCmdAction", 'click', function () {
    console.log('listCmdAction / click');
    var type = $(this).attr('data-type');
    var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
    //var el = $(this).closest('.' + type).find('.cmdAttr[data-l1key=cmd][data-l2key=alerteAction]');
    //var el = $(this).closest('.' + type).find('.cmdAttr[data-l1key=cmd][data-l2key=alerteAction]');
    jeedom.cmd.getSelectModal({
        cmd: {
            type: 'action'
        }
    }, function (result) {
        el.value(result.human);
        jeedom.cmd.displayActionOption(el.value(), '', function (html) {
            el.closest('.' + type).find('.actionOptions').html(html);
        });
    });
});

/**$("body").delegate(".listCmdAction", 'click', function () {
    console.log('gestion action alerte');
    var type = $(this).attr('data-type');
    var el = $(this).closest('.' + type).find('.cmdAttr[data-l1key=configuration][data-l2key=alerteAction]');
    jeedom.cmd.getSelectModal({
        cmd: {
            type: 'action'
        }
    }, function (result) {
        el.value(result.human);
    });
});
**/

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appelé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    console.log('addCmdToTable');
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
	tr += '<td>';
	tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" /> {{Historiser}}<br/></span>';
	tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" /> {{Affichage}}<br/></span>';
	tr += '</td>';		
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

$('body').delegate('.cmdAction.expressionAttr[data-l1key=cmd]', 'focusout', function (event) {
    console.log('focusout');
    var type = $(this).attr('data-type');
    var expression = $(this).closest('.' + type).getValues('.expressionAttr');
    var el = $(this);
    jeedom.cmd.displayActionOption($(this).value(), init(expression[0].options), function (html) {
        el.closest('.' + type).find('.actionOptions').html(html);
    });

});

$("body").delegate('.bt_removeAction', 'click', function () {
    var type = $(this).attr('data-type');
    $(this).closest('.' + type).remove();
});

function saveEqLogic(_eqLogic) {
    console.log('save configuration');
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }

    _eqLogic.configuration.addActionOnAlert = $('#div_addActionOnAlert .addActionOnAlert').getValues('.expressionAttr');

    return _eqLogic;
}

function printEqLogic(_eqLogic) {

    console.log('print configuration');
    $('#div_addActionOnAlert').empty();

    if (isset(_eqLogic.configuration)) {
        if (isset(_eqLogic.configuration.addActionOnAlert)) {
            for (var i in _eqLogic.configuration.addActionOnAlert) {
                addAction(_eqLogic.configuration.addActionOnAlert[i], 'addActionOnAlert');
            }
        }
    }
}

function addAction(_action, _type) {
    console.log('addaction (' + _action.cmd + '/' + _action.options + ', ' + _type + ')');
    var div = '<div class="' + _type + '">';
    div += '<div class="form-group ">';
    div += '<label class="col-sm-1 control-label">{{Action}}</label>';
    div += '<div class="col-sm-1">';
    div += '<a class="btn btn-default btn-sm listCmdAction" data-type="' + _type + '"><i class="fa fa-list-alt"></i></a>';
    div += '</div>';
    div += '<div class="col-sm-3">';
    div += '<input class="expressionAttr form-control input-sm cmdAction" data-l1key="cmd" data-type="' + _type + '" />';
    div += '</div>';
    div += '<div class="col-sm-6 actionOptions">';
    div += jeedom.cmd.displayActionOption(init(_action.cmd, ''), _action.options);
    div += '</div>';
    div += '<div class="col-sm-1">';
    div += '<i class="fa fa-minus-circle pull-right cursor bt_removeAction" data-type="' + _type + '"></i>';
    div += '</div>';
    div += '</div>';
    $('#div_' + _type).append(div);
    $('#div_' + _type + ' .' + _type + ':last').setValues(_action, '.expressionAttr');
}
