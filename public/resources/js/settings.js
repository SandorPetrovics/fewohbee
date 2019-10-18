/**
 * Replaces a occurence in a string
 * @param {string} text to look into
 * @param {string} replacement
 * @param {string} placeholderString
 * @returns {string}
 */
function replacePlaceholder(text, replacement, placeholderString) {
    placeholderString = placeholderString || 'placeholder';
    return text.replace(placeholderString, replacement);
}

/**
 * Loads content into the modal via get
 * @param {string} url
 * @param {string} title
 * @param {type} successFunc
 */
function getContentForModal(url, title, successFunc) {
    title = title || "";
    successFunc = successFunc || function(){};
    $("#modalCenter .modal-title").text(title);
    $("#modal-content-ajax").html(modalLoader);
    $("#modal-content-ajax").load(url, function (response, status, xhr) {
        successFunc();
    });
}

/**
 * Show or hide the delete row in a table when clicking on the delete icon for an entry
 * @param {int} id
 * @returns {Boolean}
 */
function collapseEntry(id) {
    var row = "#entry-" + id;
    var cell = "#entry-cell-" + id;
    if ($(row).is(':hidden')) {
        $(row).removeClass('d-none');
        return true;
    } else {
        $(row).addClass('d-none');
        $(cell).html(loader);
        return false;
    }
}

/**
 * Enables the edit form when clicking on edit button
 * @param {int} id
 */
function enableEditForm(id) {
    var formFieldsetPrimary = "#entry-form-fieldset-" + id;
    var editButtonArea = "#edit-button-area";
    var saveButton = "#entry-submit-" + id;
    $(editButtonArea).addClass('d-none');
    $(saveButton).removeClass('d-none');
    $(formFieldsetPrimary).removeAttr('disabled');
}

function _deleteEntry(id, url) {
    if (collapseEntry(id)) {
        var cell = "#entry-cell-" + id;
        $(cell).load(url, function (response, status, xhr) {
            //if(status == "success") location.reload();
        });
    }
    return false;
}

function _doPost(formId, url, successUrl) {
    successUrl = successUrl || "";
    $.ajax({
        url: url,
        type: "POST",
        data: $(formId).serialize(),
        error: function (xhr, ajaxOptions, thrownError) {
            alert(xhr.status);
        },
        success: function (data) {
            if(successUrl.length > 0 ) {
                location.href = successUrl;
            } else {
                location.reload();
            }
            
            
        }
    });
    return false;
}
