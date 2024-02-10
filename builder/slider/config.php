<?php return [
    'active' => 1,
    'title' => 'Slider',
    'type' => 'gallery',
    'id' => 'slider',
    'order' => 7,
    'script' => '<script>function onAddSlide(target) {
    let parent=target.closest(".slide");
    let attrId=parent.find(".image_for_field").attr("id").replace("image_for_","");
    let oldimg=parent.find(".image_for_field").attr("data-image");
    let counts=parent.parent().find(".slide").length;
    let elemnt=parent.html().replaceAll(attrId, attrId+counts).replaceAll(oldimg, attrId+counts);
    parent.after("<div class=\"slide row form-row\">"+elemnt+"</div>");documentDirty=true;}
    function onDeleteSlide(target){let parent=target.closest(".slide");alertify.confirm("'.__('sSettings::global.are_you_sure').'","'.__('sSettings::global.deleted_irretrievably').'",function(){alertify.error("'.__('sSettings::global.deleted').'");parent.remove()},function(){alertify.success("'.__('sSettings::global.canceled').'")}).set("labels",{ok:"'.__('global.delete').'",cancel:"'.__('global.cancel').'"}).set({transition:"zoom"});documentDirty=true}</script>',
];
