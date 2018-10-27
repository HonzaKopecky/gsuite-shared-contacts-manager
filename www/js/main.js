/**
 * Created by honza on 28.04.2017.
 */

$=jQuery;
crSel = '#imageCropper > img';

$(function () {
    $.nette.init();
});


$(document).ready(function() {

    $('#select-all').change(function(){
       if($(this).is(":checked")) {
           $('tbody input[type=checkbox]').attr("checked","true");
       } else {
           $('tbody input[type=checkbox]').removeAttr("checked");
       }
    });

    $('select').material_select();

    $('#createContactFormSubmit').click(function() {
        $("#frm-createContactForm").submit();
    });

    $('#editContactFormSubmit').click(function() {
        $("#frm-editContactForm").submit();
    });

    var cr = $(crSel);

    options = {
        aspectRatio: 1,
        viewMode: 1
    };

    if(cr.length > 0) {
        if($(cr).attr("src") != "")
            $(cr).cropper(options);
    }

    $('input[name="profileUpload"]').change(function() {
        cr = $(crSel);
        if(!cr)
            return console.log('Cropper not found');
        URL = window.URL || window.webkitURL;
        var files = this.files;
        if(files.length != 1)
            return $(this).val('');
        var file = files[0];
        if(file.type != "image/jpeg" && file.type != "image/bmp" && file.type != "image/png" && file.type != "image/gif")
            return $(this).val('');
        var imageURL = URL.createObjectURL(file);
        if($(cr).data('cropper'))
            $(cr).cropper('destroy');
        $(cr).attr("src", imageURL).cropper(options);
        $(this).val('');
    });

    $("#uploadCroppedPicture").click(function() {
        if(!$(crSel).data('cropper'))
            return alert("Upload an image first.");
        btn = this;
        var blob = $(crSel).cropper('getCroppedCanvas', {width: 100, height: 100}).toBlob(function(blobData) {
            processImageUpload(blobData, btn)
        });
    });

    function processImageUpload(blobData, btn) {
        var target = $(btn).attr("ajax-location");
        var formData = new FormData();
        formData.append('croppedImage', blobData);
        var uploadingToast = null;
        $.ajax(target, {
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                $('.uploading-in-process').fadeOut('normal', function() {
                    $(this).remove();
                    Materialize.toast('Image uploaded!',1500);
                });
            },
            error: function () {
                $('.uploading-in-process').fadeOut('normal', function() {
                    $(this).remove();
                    Materialize.toast('Image upload failed.',1500,'red darken-4');
                });
            }
        });
        uploadingToast = Materialize.toast('Uploading...',60000,'uploading-in-process');
    }

    initializeAutofill();
});

function toObject(arr) {
    var obj = {};
    if(arr == null)
        return null;
    for(var i = 0; i < arr.length; i++)
        obj[arr[i]] = null;
    return obj;
}

function initializeAutofill() {
    if(typeof(autofill) === 'undefined')
        return;
    autoarray = JSON.parse(autofill);
    if(autoarray['address']) {
        $('input[name="gd_address_street"]').autocomplete({
            data: toObject(autoarray['address']['street'])
        });
        $('input[name="gd_address_postcode"]').autocomplete({
            data: toObject(autoarray['address']['postcode'])
        });
        $('input[name="gd_address_city"]').autocomplete({
            data: toObject(autoarray['address']['city'])
        });
        $('input[name="gd_address_region"]').autocomplete({
            data: toObject(autoarray['address']['region'])
        });
        $('input[name="gd_address_country"]').autocomplete({
            data: toObject(autoarray['address']['country'])
        });
    }
    if(autoarray['organization']) {
        $('input[name="gd_organization_name"]').autocomplete({
            data: toObject(autoarray['organization']['organization'])
        });
        $('input[name="gd_organization_department"]').autocomplete({
            data: toObject(autoarray['organization']['department'])
        });
        $('input[name="gd_job_title"]').autocomplete({
            data: toObject(autoarray['organization']['job'])
        });
    }
    if(autoarray['customs']) {
        $('input[name="gd_custom_key"]').autocomplete({
            data: toObject(autoarray['customs'])
        });
    }
    $('.autocomplete-content').siblings('input').attr("autocomplete","off");
}