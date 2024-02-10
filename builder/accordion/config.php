<?php return [
    'active' => 1,
    'title' => 'Accordion',
    'type' => 'richtext',
    'id' => 'accordion',
    'order' => 4,
    'script' => '<script>function onAddAccord(target){
    tinymce.remove();
    let parent=target.closest(".accord");
    let attrId=parent.find("textarea").attr("id");
    let dataId=parent.find("textarea").attr("data-id");
    if(dataId == ""){dataId=attrId;}
    let counts=parent.parent().find(".accord").length;
    let elemnt=parent.clone();
    elemnt.find("input").attr("value", "");
    elemnt.find("textarea").text("");
    elemnt=elemnt.html().replaceAll(attrId, dataId + counts);
    parent.after("<div class=\"accord row form-row\">"+elemnt+"</div>");'
    .(evo()->getConfig('sart_tinymce5_theme')??"custom").'.selector = selector_'.(evo()->getConfig('sart_tinymce5_theme')??"custom").' = selector_'.(evo()->getConfig('sart_tinymce5_theme')??"custom").' + \',#\' + dataId + counts;
    tinymce.init('.(evo()->getConfig('sart_tinymce5_theme')??"custom").');
    documentDirty=true;}
    function onDeleteAccord(target){let parent=target.closest(".accord");alertify.confirm("'.__('sSettings::global.are_you_sure').'","'.__('sSettings::global.deleted_irretrievably').'",function(){alertify.error("'.__('sSettings::global.deleted').'");parent.remove()},function(){alertify.success("'.__('sSettings::global.canceled').'")}).set("labels",{ok:"'.__('global.delete').'",cancel:"'.__('global.cancel').'"}).set({transition:"zoom"});documentDirty=true}</script>',
];
