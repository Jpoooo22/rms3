$(function() {
    'use strict';

    $('.form-control').on('input', function() {
        var $field = $(this).closest('.form-group');
        if (this.value) {
            $field.addClass('field--not-empty');
        } else {
            $field.removeClass('field--not-empty');
        }
    });

});

// first login page 
$(document).ready(function(e) {

    let $uploadfile = $('#profile_img_secation .upload-profile-image input[type="file"]');

    $uploadfile.change(function() {
        readURL(this);
    });



});
// first login readURL for profile page 
function readURL(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $("#profile_img_secation .upload-profile-image .img").attr('src', e.target.result);
            $("#profile_img_secation .upload-profile-image .camera-icon").css({ display: "none" });
        }

        reader.readAsDataURL(input.files[0]);

    }
}