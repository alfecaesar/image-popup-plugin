jQuery(document).ready((function(e){var i;e("#image_popup_media_button_2").on("click",(function(_){_.preventDefault(),i||(i=wp.media.frames.file_frame=wp.media({title:"Select or Upload Image",button:{text:"Use this image"},multiple:!1})).on("select",(function(){var _=i.state().get("selection").first().toJSON();e("#image_popup_media_id_2").val(_.id),e("#image_popup_media_preview_2").attr("src",_.url).show(),e("#image_popup_media_remove_button_2").show()})),i.open()})),e("#image_popup_media_remove_button_2").on("click",(function(){e("#image_popup_media_id_2").val(""),e("#image_popup_media_preview_2").hide(),e(this).hide()})),e("#image_popup_media_button_3").on("click",(function(_){_.preventDefault(),i||(i=wp.media.frames.file_frame=wp.media({title:"Select or Upload Image",button:{text:"Use this image"},multiple:!1})).on("select",(function(){var _=i.state().get("selection").first().toJSON();e("#image_popup_media_id_3").val(_.id),e("#image_popup_media_preview_3").attr("src",_.url).show(),e("#image_popup_media_remove_button_3").show()})),i.open()})),e("#image_popup_media_remove_button_3").on("click",(function(){e("#image_popup_media_id_3").val(""),e("#image_popup_media_preview_3").hide(),e(this).hide()})),e("#image_popup_media_button_4").on("click",(function(_){_.preventDefault(),i||(i=wp.media.frames.file_frame=wp.media({title:"Select or Upload Image",button:{text:"Use this image"},multiple:!1})).on("select",(function(){var _=i.state().get("selection").first().toJSON();e("#image_popup_media_id_4").val(_.id),e("#image_popup_media_preview_4").attr("src",_.url).show(),e("#image_popup_media_remove_button_4").show()})),i.open()})),e("#image_popup_media_remove_button_4").on("click",(function(){e("#image_popup_media_id_4").val(""),e("#image_popup_media_preview_4").hide(),e(this).hide()})),e("#image_popup_media_button_5").on("click",(function(_){_.preventDefault(),i||(i=wp.media.frames.file_frame=wp.media({title:"Select or Upload Image",button:{text:"Use this image"},multiple:!1})).on("select",(function(){var _=i.state().get("selection").first().toJSON();e("#image_popup_media_id_5").val(_.id),e("#image_popup_media_preview_5").attr("src",_.url).show(),e("#image_popup_media_remove_button_5").show()})),i.open()})),e("#image_popup_media_remove_button_5").on("click",(function(){e("#image_popup_media_id_5").val(""),e("#image_popup_media_preview_5").hide(),e(this).hide()}))}));