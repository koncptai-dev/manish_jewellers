"use strict";

let elementProductTypeByID = $("#product_type");
let elementAdditionalImageColumn = $(".additional_image_column");
let elementCustomUploadInputFileByID = $(".custom-upload-input-file");
let elementDigitalProductTypeByID = $("#digital_product_type");
let elementProductColorSwitcherByID = $("#product-color-switcher");
let elementImagePathOfProductUploadIconByID = $(
    "#image-path-of-product-upload-icon"
).data("path");
let messageEnterChoiceValues = $("#message-enter-choice-values").data("text");
let messageUploadImage = $("#message-upload-image").data("text");
let messageFileSizeTooBig = $("#message-file-size-too-big").data("text");
let messagePleaseOnlyInputPNGOrJPG = $(
    "#message-please-only-input-png-or-jpg"
).data("text");
let messageAreYouSure = $("#message-are-you-sure").data("text");
let messageYesWord = $("#message-yes-word").data("text");
let messageNoWord = $("#message-no-word").data("text");
let messageWantAddOrUpdateThisProduct = $(
    "#message-want-to-add-or-update-this-product"
).data("text");
let getSystemCurrencyCode = $("#system-currency-code").data("value");

$(document).on("ready", function () {
    $(".summernote").summernote({
        height: 150,
        toolbar: [
            ["style", ["bold", "italic", "underline", "clear"]],
            ["font", ["strikethrough", "superscript", "subscript"]],
            ["fontsize", ["fontsize"]],
            ["color", ["color"]],
            ["para", ["ul", "ol", "paragraph"]],
            ["height", ["height"]],
        ],
        callbacks: {
            onChange: function (contents, $editable) {
                if ($(this).hasClass("product-description-default-language")) {
                    var textWithoutTagsAndEntities = contents.replace(
                        /<[^>]+>|&[^;]+;/g,
                        ""
                    );
                    var maxLength = 160;
                    if (textWithoutTagsAndEntities.length > maxLength) {
                        textWithoutTagsAndEntities =
                            textWithoutTagsAndEntities.substring(0, maxLength);
                    }
                    $("#meta_description").val(textWithoutTagsAndEntities);
                }
            },
        },
    });

    getProductTypeFunctionality();
    getDigitalProductTypeFunctionality();

    if ($("#product-color-switcher").prop("checked")) {
        $("#color-wise-image-area").show();
        colorWiseImageFunctionality($("#colors-selector"));
    } else {
        $("#color-wise-image-area").hide();
    }

    $(".color-var-select").select2({
        templateResult: colorCodeSelect,
        templateSelection: colorCodeSelect,
        escapeMarkup: function (m) {
            return m;
        },
    });

    function colorCodeSelect(state) {
        let colorCode = $(state.element).val();
        if (!colorCode) return state.text;
        return (
            "<span class='color-preview' style='background-color:" +
            colorCode +
            ";'></span>" +
            state.text
        );
    }
});

function getProductTypeFunctionality() {
    let productType = elementProductTypeByID.val();
    if (productType && productType.toString() === "physical") {
        elementDigitalProductTypeByID.val(
            $("#digital_product_type option:first").val()
        );
        elementProductColorSwitcherByIDFunctionality("reset");
        $("#digital_product_type_show").hide();
        $("#digital_file_ready").val("");
        $(".physical_product_show").show();
        $(".digital-product-sections-show").hide();
        $(".digitalProductVariationSetupSection").hide();
    } else if (productType && productType.toString() === "digital") {
        elementProductColorSwitcherByID.prop("checked", false);
        $("#color-wise-image-section").empty().html("");
        elementProductColorSwitcherByIDFunctionality("reset");
        $("#digital_product_type_show").show();
        $(".digital-product-sections-show").show();
        $(".digitalProductVariationSetupSection").show();
        $(".physical_product_show").hide();
    }

    try {
        if (productType && productType.toString() === "physical") {
            $("#digital-product-variation-section").empty().html();
            $(
                "#digital-product-type-choice-section .extension-choice-section"
            ).remove();
        }
    } catch (e) {}
}

function getDigitalProductTypeFunctionality() {
    let digitalProductType = elementDigitalProductTypeByID.val();
    getUpdateDigitalVariationFunctionality();
}

elementProductTypeByID.on("change", () => getProductTypeFunctionality());
elementDigitalProductTypeByID.on("change", () =>
    getDigitalProductTypeFunctionality()
);

elementProductColorSwitcherByID.on("click", function () {
    elementProductColorSwitcherByIDFunctionality();
});

let pageLoadFirstTime = true;
function elementProductColorSwitcherByIDFunctionality(action = null) {
    if (elementProductColorSwitcherByID.prop("checked")) {
        $(".color_image_column").removeClass("d-none");
        elementAdditionalImageColumn.removeClass("col-md-9");
        elementAdditionalImageColumn.addClass("col-md-12");
        $("#color-wise-image-area").show();
        $("#additional_Image_Section .col-md-4").addClass("col-lg-2");
    } else {
        let colors = $("#colors-selector");
        let choiceAttributes = $("#choice_attributes");

        colors.val(null).trigger("change");
        if (pageLoadFirstTime === false && action === "reset") {
            choiceAttributes.val(null).trigger("change");
            pageLoadFirstTime = false;
        }

        $(".color_image_column").addClass("d-none");
        elementAdditionalImageColumn.addClass("col-md-9");
        elementAdditionalImageColumn.removeClass("col-md-12");
        $("#color-wise-image-area").hide();
        $("#additional_Image_Section .col-md-4").removeClass("col-lg-2");
    }

    if (!$('input[name="colors_active"]').is(":checked")) {
        $("#colors-selector").prop("disabled", true);
    } else {
        $("#colors-selector").prop("disabled", false);
    }
}

$(document).on("ready", function () {
    if (elementProductColorSwitcherByID.prop("checked")) {
        $(".color_image_column").removeClass("d-none");
        elementAdditionalImageColumn.removeClass("col-md-9");
        elementAdditionalImageColumn.addClass("col-md-12");
        $("#additional_Image_Section .col-md-4").addClass("col-lg-2");
    } else {
        $(".color_image_column").addClass("d-none");
        elementAdditionalImageColumn.addClass("col-md-9");
        elementAdditionalImageColumn.removeClass("col-md-12");
        $("#additional_Image_Section .col-md-4").removeClass("col-lg-2");
    }
});

$('input[name="colors_active"]').on("change", function () {
    if (!$('input[name="colors_active"]').is(":checked")) {
        $("#colors-selector").prop("disabled", true);
    } else {
        $("#colors-selector").prop("disabled", false);
    }
});

$("#choice_attributes").on("change", function () {
    $("#unit_price").val("");
    $("#sku_combination").empty().html("");
    $("#customer_choice_options").empty().html("");
    $.each($("#choice_attributes option:selected"), function () {
        addMoreCustomerChoiceOption($(this).val(), $(this).text());
    });
    getUpdateSKUFunctionality();
});

$("#colors-selector").on("change", function () {
    getUpdateSKUFunctionality();
    if (elementProductColorSwitcherByID.prop("checked")) {
        colorWiseImageFunctionality($("#colors-selector"));
        $("#color-wise-image-area").show();
    } else {
        $("#color-wise-image-area").hide();
    }
});

$('#unit_price').on("change", function () {
    console.log("unit_price_val");
    let productType = elementProductTypeByID.val();
    if (productType && productType.toString() === "physical") {
        getUpdateSKUFunctionality();
    }
    getUpdateDigitalVariationFunctionality();
    setTimeout(() => {
        $(".variation-price-input").val($(this).val());
    }, 500);
});

function getUpdateSKUFunctionality() {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    $.ajax({
        type: "POST",
        url: $("#route-admin-products-sku-combination").data("url"),
        data: $("#product_form").serialize(),
        success: function (data) {
            $("#sku_combination").html(data.view);
            updateProductQuantity();
            updateProductQuantityByKeyUp();
            let productType = elementProductTypeByID.val();
            if (productType && productType.toString() === "physical") {
                if (data.length > 1) {
                    $("#quantity").hide();
                } else {
                    $("#quantity").show();
                }
            }
            generateSKUPlaceHolder();
            removeSymbol();
        },
    });
}

$("#discount_type").on("change", function () {
    if ($(this).val().toString() === "flat") {
        $(".discount_amount_symbol")
            .html(`(` + getSystemCurrencyCode + `)`)
            .fadeIn();
    } else {
        $(".discount_amount_symbol").html("(%)").fadeIn();
    }
});

$(".action-add-more-image").on("change", function () {
    let parentDiv = $(this).closest("div");
    parentDiv.find(".delete_file_input").removeClass("d-none");
    parentDiv.find(".delete_file_input").fadeIn();
    addMoreImage(this, $(this).data("target-section"));
});

function addMoreImage(thisData, targetSection) {
    let $fileInputs = $(targetSection + " input[type='file']");
    let nonEmptyCount = 0;
    $fileInputs.each(function () {
        if (parseFloat($(this).prop("files").length) === 0) {
            nonEmptyCount++;
        }
    });

    uploadColorImage(thisData);

    if (nonEmptyCount === 0) {
        let datasetIndex = thisData.dataset.index + 1;

        let newHtmlData =
            `<div class="col-sm-12 col-md-4">
                        <div class="custom_upload_input position-relative border-dashed-2">
                            <input type="file" name="${thisData.name}" class="custom-upload-input-file action-add-more-image" data-index="${datasetIndex}" data-imgpreview="additional_Image_${datasetIndex}"
                                accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" data-target-section="${targetSection}">

                            <span class="delete_file_input delete_file_input_section btn btn-outline-danger btn-sm square-btn d-none">
                                <i class="tio-delete"></i>
                            </span>

                            <div class="img_area_with_preview position-absolute z-index-2 border-0">
                                <img alt="" id="additional_Image_${datasetIndex}" class="h-auto aspect-1 bg-white d-none" src="img">
                            </div>
                            <div class="position-absolute h-100 top-0 w-100 d-flex align-content-center justify-content-center">
                                <div class="d-flex flex-column justify-content-center align-items-center">
                                    <img src="` +
            elementImagePathOfProductUploadIconByID +
            `" class="w-50" alt="">
                                    <h3 class="text-muted">` +
            messageUploadImage +
            `</h3>
                                </div>
                            </div>
                        </div>
                    </div>`;

        $(targetSection).append(newHtmlData);
    }

    elementCustomUploadInputFileByID.on("change", function () {
        if (parseFloat($(this).prop("files").length) !== 0) {
            let parentDiv = $(this).closest("div");
            parentDiv.find(".delete_file_input").fadeIn();
        }
    });

    $(".delete_file_input_section").click(function () {
        $(this).closest("div").parent().remove();
    });

    if (elementProductColorSwitcherByID.prop("checked")) {
        $("#additional_Image_Section .col-md-4").addClass("col-lg-2");
    } else {
        $("#additional_Image_Section .col-md-4").removeClass("col-lg-2");
    }

    $(".action-add-more-image").on("change", function () {
        let parentDiv = $(this).closest("div");
        parentDiv.find(".delete_file_input").removeClass("d-none");
        parentDiv.find(".delete_file_input").fadeIn();
        addMoreImage(this, $(this).data("target-section"));
    });

    $(".onerror-add-class-d-none").on("error", function () {
        $(this).addClass("d-none");
    });

    onErrorImage();
}

$(function () {
    $("#coba").spartanMultiImagePicker({
        fieldName: "images[]",
        maxCount: 15,
        rowHeight: "auto",
        groupClassName: "col-6 col-md-4 col-lg-3 col-xl-2",
        maxFileSize: "",
        placeholderImage: {
            image: $("#image-path-of-product-upload-icon-two").data("path"),
            width: "100%",
        },
        dropFileLabel: "Drop Here",
        onAddRow: function (index, file) {},
        onRenderedPreview: function (index) {},
        onRemoveRow: function (index) {},
        onExtensionErr: function () {
            toastr.error(messagePleaseOnlyInputPNGOrJPG, {
                CloseButton: true,
                ProgressBar: true,
            });
        },
        onSizeErr: function () {
            toastr.error(messageFileSizeTooBig, {
                CloseButton: true,
                ProgressBar: true,
            });
        },
    });
});

function addMoreCustomerChoiceOption(index, name) {
    let nameSplit = name.split(" ").join("");
    let genHtml =
        `<div class="col-md-6"><div class="form-group">
                <input type="hidden" name="choice_no[]" value="${index}">
                    <label class="title-color">${nameSplit}</label>
                    <input type="text" name="choice[]" value="${nameSplit}" hidden>
                    <div class="">
                        <input type="text" class="form-control" id="choice_options" name="choice_options_${index}[]"
                        placeholder="` +
        messageEnterChoiceValues +
        `" data-role="tagsinput" onchange="getUpdateSKUFunctionality()">
                    </div>
                </div>
        </div>`;
    $("#customer_choice_options").append(genHtml);
    $(
        "input[data-role=tagsinput], select[multiple][data-role=tagsinput]"
    ).tagsinput();
}

$(".delete_file_input").on("click", function () {
    let $parentDiv = $(this).parent().parent();
    $parentDiv.find('input[type="file"]').val("");
    $parentDiv.find(".img_area_with_preview img").addClass("d-none");
    $(this).removeClass("d-flex");
    $(this).hide();
});

$(".delete_preview_file_input").on("click", function () {
    let parentDiv = $(this).parent().parent();
    parentDiv.find('input[type="file"]').val("");
    parentDiv
        .find(".image-uploader__title")
        .html($(".image-uploader__title").data("default"));
    $(this).removeClass("delete_preview_file_input");

    let formData = new FormData(document.getElementById("product_form"));
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.post({
        url: $(this).data("route"),
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            if (response.errors) {
                for (let i = 0; i < response.errors.length; i++) {
                    toastr.error(response.errors[i].message, {
                        CloseButton: true,
                        ProgressBar: true,
                    });
                }
            } else {
                toastr.success(response.message);
                parentDiv
                    .find(".image-uploader__title")
                    .html($(".image-uploader__title").data("default"));
            }
        },
    });
});

$(".onerror-add-class-d-none").on("error", function () {
    $(this).addClass("d-none");
});

function uploadColorImage(thisData = null) {
    if (thisData) {
        document
            .getElementById(thisData.dataset.imgpreview)
            .setAttribute("src", window.URL.createObjectURL(thisData.files[0]));
        document
            .getElementById(thisData.dataset.imgpreview)
            .classList.remove("d-none");

        try {
            if (
                thisData.dataset.imgpreview == "pre_img_viewer" &&
                !$("#meta_image_input").val()
            ) {
                $("#pre_meta_image_viewer").removeClass("d-none");
                $(".pre-meta-image-viewer").attr(
                    "src",
                    window.URL.createObjectURL(thisData.files[0])
                );
            }
        } catch (e) {}
    }
}

$(".action-upload-color-image").on("change", function () {
    uploadColorImage(this);
});

$(".delete_file_input").click(function () {
    let $parentDiv = $(this).closest("div");
    $parentDiv.find('input[type="file"]').val("");
    $parentDiv.find(".img_area_with_preview img").addClass("d-none");
    $(this).hide();
});

elementCustomUploadInputFileByID.on("change", function () {
    if (parseFloat($(this).prop("files").length) !== 0) {
        let $parentDiv = $(this).closest("div");
        $parentDiv.find(".delete_file_input").fadeIn();
    }
});

$(".product-add-requirements-check").on("click", function () {
    getProductAddRequirementsCheck();
});

$(".action-onclick-generate-number").on("click", function () {
    let getElement = $(this).data("input");
    $(getElement).val(generateRandomString(6));
    generateSKUPlaceHolder();
});

function generateRandomString(length) {
    let result = "";
    let characters = "012345ABCDEFGHIJKLMNOPQRSTUVWXYZ3456789";
    let charactersLength = characters.length;
    for (let i = 0; i < length; i++) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
    }
    return result;
}

function getProductAddRequirementsCheck() {
    Swal.fire({
        title: messageAreYouSure,
        text: messageWantAddOrUpdateThisProduct,
        type: "warning",
        showCancelButton: true,
        cancelButtonColor: "default",
        confirmButtonColor: "#377dff",
        cancelButtonText: messageNoWord,
        confirmButtonText: messageYesWord,
        reverseButtons: true,
    }).then((result) => {
        if (result.value) {
            let discountValue = parseFloat($("#discount").val());
            let submitStatus = 1;
            $(".variation-price-input").each(function () {
                let variationPrice = parseFloat($(this).val());
                if (variationPrice < discountValue) {
                    toastr.error(
                        $(
                            "#message-discount-will-not-larger-then-variant-price"
                        ).data("text")
                    );
                    submitStatus = 0;
                    return false;
                }
            });

            if (submitStatus === 1) {
                let formData = new FormData(
                    document.getElementById("product_form")
                );
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                            "content"
                        ),
                    },
                });
                $.post({
                    url: $("#product_form").attr("action"),
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                        $("#loading").fadeIn();
                    },
                    success: function (data) {
                        if (data.errors) {
                            for (let i = 0; i < data.errors.length; i++) {
                                toastr.error(data.errors[i].message, {
                                    CloseButton: true,
                                    ProgressBar: true,
                                });
                            }
                        } else {
                            toastr.success(
                                $("#message-product-added-successfully").data(
                                    "text"
                                ),
                                {
                                    CloseButton: true,
                                    ProgressBar: true,
                                }
                            );
                            $("#product_form").submit();
                        }
                    },
                    complete: function () {
                        $("#loading").fadeOut();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error("Error:", textStatus, errorThrown);
                        $("#loading").fadeOut();
                    },
                });
            }
        }
    });
}

$("#generate_number").on("keyup", function () {
    generateSKUPlaceHolder();
});

function generateSKUPlaceHolder() {
    let newPlaceholderValue =
        $("#get-example-text").data("example") +
        " : " +
        $("input[name=code]").val() +
        "-MCU-47-V593-M";
    $(".store-keeping-unit").attr("placeholder", newPlaceholderValue);
}

$(window).on("load", function () {
    generateSKUPlaceHolder();
});

$("#digital-product-type-select").on("change", function () {
    $(
        "#digital-product-type-choice-section .extension-choice-section"
    ).remove();
    $("#digital-product-variation-section").empty().html();
    $.each($("#digital-product-type-select option:selected"), function () {
        addMoreDigitalProductChoiceOption($(this).val(), $(this).text());
    });
    getUpdateDigitalVariationFunctionality();
});

function addMoreDigitalProductChoiceOption(index, name) {
    let nameSplit = name.split(" ").join("");
    let ExtensionText = $("#get-extension-text-message").data("text");
    let genHtml =
        `<div class="col-sm-6 col-md-4 col-xxl-3 extension-choice-section">
                <div class="form-group">
                    <input type="hidden" name="extensions_type[]" value="${index}">
                    <label class="title-color">${nameSplit} ${ExtensionText}</label>
                    <input type="text" name="extensions[]" value="${nameSplit}" hidden>
                    <div class="">
                        <input type="text" class="form-control" name="extensions_options_${index}[]"
                        placeholder="` +
        messageEnterChoiceValues +
        `" data-role="tagsinput" onchange="getUpdateDigitalVariationFunctionality()">
                    </div>
                </div>
        </div>`;
    $("#digital-product-type-choice-section").append(genHtml);
    $(
        "input[data-role=tagsinput], select[multiple][data-role=tagsinput]"
    ).tagsinput();
}

function getUpdateDigitalVariationFunctionality() {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    $.ajax({
        type: "POST",
        url: $("#route-admin-products-digital-variation-combination").data(
            "url"
        ),
        data: $("#product_form").serialize(),
        success: function (data) {
            $("#digital-product-variation-section").html(data.view);
            ProductVariationFileUploadFunctionality();
            deleteDigitalVariationFileFunctionality();
        },
    });
}

function deleteDigitalVariationFileFunctionality() {
    $(".digital-variation-file-delete-button").on("click", function () {
        let variantKey = $(this).data("variant");
        let productId = $(this).data("product");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });
        $.ajax({
            type: "POST",
            url: $("#route-admin-products-digital-variation-file-delete").data(
                "url"
            ),
            data: {
                product_id: productId,
                variant_key: variantKey,
            },
            success: function (response) {
                getUpdateDigitalVariationFunctionality();
                response.status === 1
                    ? toastr.success(response.message)
                    : toastr.error(response.message);
            },
        });
    });
}

function ProductVariationFileUploadFunctionality() {
    $('.variation-upload-item input[type="file"]').each(function () {
        $(this).on("change", function () {
            const file = $(this)[0].files[0];
            if (file) {
                $(this)
                    .closest(".variation-upload-item")
                    .find(".variation-upload-file")
                    .addClass("collapse");
                $(this)
                    .closest(".variation-upload-item")
                    .find(".uploading-item")
                    .removeClass("collapse");
                const timer = setTimeout(() => {
                    $(this)
                        .closest(".variation-upload-item")
                        .find(".uploading-item")
                        .addClass("collapse");
                    $(this)
                        .closest(".variation-upload-item")
                        .find(".uploaded-item")
                        .removeClass("collapse");
                    $(this)
                        .closest(".variation-upload-item")
                        .find(".uploaded-item .file-name")
                        .text(file.name);
                }, 500);
                return () => clearTimeout(timer);
            }
        });
    });
    $(".cancel-upload").on("click", function () {
        $(this)
            .closest(".variation-upload-item")
            .find(".variation-upload-file")
            .removeClass("collapse");
        $(this)
            .closest(".variation-upload-item")
            .find(".uploading-item")
            .addClass("collapse");
        $(this)
            .closest(".variation-upload-item")
            .find(".uploaded-item")
            .addClass("collapse");
        $(this)
            .closest(".variation-upload-item")
            .find('input[type="file"]')
            .val("");
    });
}

$(".product-title-default-language").on("change keyup keypress", function () {
    $("#meta_title").val($(this).val());
    getUpdateDigitalVariationFunctionality();
});

$(".image-uploader__zip").on("change", function (event) {
    const file = event.target.files[0];
    const target = $(this)
        .closest(".image-uploader")
        .find(".image-uploader__title");
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            target.text(file.name);
        };
        reader.readAsDataURL(file);
        $(".zip-remove-btn").show();
    } else {
        target.text("Upload File");
        $(".zip-remove-btn").hide();
    }
});
$(".image-uploader .zip-remove-btn").on("click", function (event) {
    $(this).closest(".image-uploader").find(".image-uploader__zip").val(null);
    $(this)
        .closest(".image-uploader")
        .find(".image-uploader__title")
        .text("Upload File");
    $(this).hide();
});

$.fn.select2DynamicDisplay = function () {
    function updateDisplay($element) {
        var $rendered = $element
            .siblings(".select2-container")
            .find(".select2-selection--multiple")
            .find(".select2-selection__rendered");
        var $container = $rendered.parent();
        var containerWidth = $container.width();
        var totalWidth = 0;
        var itemsToShow = [];
        var remainingCount = 0;

        // Get all selected items
        var selectedItems = $element.select2("data");

        // Create a temporary container to measure item widths
        var $tempContainer = $("<div>")
            .css({
                display: "inline-block",
                padding: "0 15px",
                "white-space": "nowrap",
                visibility: "hidden",
            })
            .appendTo($container);

        // Calculate the width of items and determine how many fit
        selectedItems.forEach(function (item) {
            var $tempItem = $("<span>")
                .text(item.text)
                .css({
                    display: "inline-block",
                    padding: "0 12px",
                    "white-space": "nowrap",
                })
                .appendTo($tempContainer);

            var itemWidth = $tempItem.outerWidth(true);

            if (totalWidth + itemWidth <= containerWidth - 40) {
                totalWidth += itemWidth;
                itemsToShow.push(item);
            } else {
                remainingCount = selectedItems.length - itemsToShow.length;
                return false;
            }
        });

        $tempContainer.remove();

        const $searchForm = $rendered.find(".select2-search");

        var html = "";
        itemsToShow.forEach(function (item) {
            html += `<li class="name">
                                    <span>${item.text}</span>
                                    <span class="close-icon" data-id="${item.id}"><i class="tio-clear"></i></span>
                                    </li>`;
        });
        if (remainingCount > 0) {
            html += `<li class="ms-auto">
                                    <div class="more">+${remainingCount}</div>
                                    </li>`;
        }
        html += $searchForm.prop("outerHTML");

        $rendered.html(html);

        function debounce(func, wait) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Attach event listener with debouncing
        $(".select2-search input").on(
            "input",
            debounce(function () {
                const inputValue = $(this).val().toLowerCase();

                const $listItems = $(".select2-results__options li");

                $listItems.each(function () {
                    const itemText = $(this).text().toLowerCase();
                    $(this).toggle(itemText.includes(inputValue));
                });
            }, 100)
        );

        $(".select2-search input").on("keydown", function (e) {
            if (e.which === 13) {
                e.preventDefault();

                const inputValue = $(this).val();
                if (
                    !inputValue ||
                    itemsToShow.find((item) => item.text === inputValue) ||
                    selectedItems.find((item) => item.text === inputValue)
                ) {
                    $(this).val("");
                    return null;
                }

                if (inputValue) {
                    $element.append(
                        new Option(inputValue, inputValue, true, true)
                    );
                    $element.val([...$element.val(), inputValue]);
                    $(this).val("");
                    $(".multiple-select2").select2DynamicDisplay();
                }
            }
        });
    }
    return this.each(function () {
        var $this = $(this);

        $this.select2({
            tags: true,
        });

        // Bind change event to update display
        $this.on("change", function () {
            updateDisplay($this);
        });

        // Initial display update
        updateDisplay($this);

        $(window).on("resize", function () {
            updateDisplay($this);
        });
        $(window).on("load", function () {
            updateDisplay($this);
        });

        // Handle the click event for the remove icon
        $(document).on(
            "click",
            ".select2-selection__rendered .close-icon",
            function (e) {
                e.stopPropagation();
                var $removeIcon = $(this);
                var itemId = $removeIcon.data("id");
                var $this2 = $removeIcon
                    .closest(".select2")
                    .siblings(".multiple-select2");
                $this2.val(
                    $this2.val().filter(function (id) {
                        return id != itemId;
                    })
                );
                $this2.trigger("change");
            }
        );
    });
};
$(".multiple-select2").select2DynamicDisplay();

$("#product_metal").on("change", function () {
    if ($("#product_metal").val() === "Silver") {
        $("#unit_price").attr("readonly", false);
    }
});
$(document).ready(function () {
    if ($("#product_metal").val() === "Silver") {
        $("#unit_price").attr("readonly", false);
    }
});
$(document).on("change", "#choice_options", function () {
    calculateUnitPrice();
});
$('input[name="choice_options_1[]"]').on("change", function () {
    calculateUnitPriceToEdit();
});
$("#hallmark_charges").on("change", function () {
    if($("#is_edit").val() == 1){
        calculateUnitPriceToEdit();
    }else{
        calculateUnitPrice();
    }
});
function calculateUnitPriceToEdit() {
    let choiceOptions = [];
    let hasWeight = false;
    let hasCarat = false;

    $("#customer_choice_options .col-lg-6").each(function () {
        let title = $(this).find('input[name="choice[]"]').val()?.trim();
        let values = [];

        $(this).find(".call-update-sku").each(function () {
            let val = $(this).val()?.trim(); // clean up value
            if (val) values.push(val);
        });

        if (title && values.length > 0) {
            choiceOptions.push({
                title: title,
                options: values,
            });

            // Track if both weight and carat are present with value
            if (title.toLowerCase() === "weight") {
                console.log(values)
                hasWeight = true;
            }
            if (title.toLowerCase() === "carat") {
                hasCarat = true;
            }
        }
    });
        let unitPrice = $("#unit_price").val();
        let makingCharges = $("#making_charges").val();
        let productMetal = $("#product_metal").val();
        let hallmark_charges = $("#hallmark_charges").val();
    // Send request only if both weight and carat are available
    if (hasWeight && hasCarat) {
        
        $.ajax({
            url: $("#calculate-unit-price").data("url"),
            method: "POST",
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            data: {
                choice_options: JSON.stringify(choiceOptions),
                unit_price: unitPrice,
                making_charges: makingCharges,
                product_metal: productMetal,
                hallmark_charges: hallmark_charges,
            },
            success: function (response) {
                if (response.unit_price !== null) {
                    let formattedPrice = parseFloat(response.unit_price).toFixed(2);
                    $("#unit_price").val(formattedPrice);

                    // Confirm value is actually set before proceeding
                    if ($("#unit_price").val() === formattedPrice) {
                        getUpdateSKUFunctionality();
                        
                    }
                }
            },
            complete: function () {
                $("#loading").fadeOut();
            },
        });
    }
}


function calculateUnitPrice() {
    let choiceOptions = [];
    let hasWeight = false;
    let hasCarat = false;

    $("#customer_choice_options .form-group").each(function () {
        let title = $(this).find("label").text().trim();
        let values = [];

        $(this)
            .find(".bootstrap-tagsinput .tag")
            .each(function () {
                let val = $(this).text().trim().replace("×", "");
                if (val !== "") values.push(val);
            });

        if (title && values.length > 0) {
            choiceOptions.push({
                title: title,
                options: values,
            });

            // Check for specific fields
            if (title.toLowerCase() === "weight") {
                hasWeight = true;
            }
            if (title.toLowerCase() === "carat") {
                hasCarat = true;
            }
        }
    });
    let unitPrice = $("#unit_price").val();
    let makingCharges = $("#making_charges").val();
    let productMetal = $("#product_metal").val();
    let hallmark_charges = $("#hallmark_charges").val();
    // Only proceed if both Weight and carat are present with values
    if (hasWeight && hasCarat && unitPrice == "") {
        $.ajax({
            url: $("#calculate-unit-price").data("url"),
            method: "POST",
            dataType: "json",
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            data: {
                choice_options: JSON.stringify(choiceOptions),
                unit_price: unitPrice,
                making_charges: makingCharges,
                product_metal: productMetal,
                hallmark_charges: hallmark_charges,
            },
            success: function (response) {
                console.log(response);
                if (response.unit_price) {
                    let formattedPrice = parseFloat(response.unit_price).toFixed(2);
                    $("#unit_price").val(formattedPrice);

                    // Confirm value is actually set before proceeding
                    if ($("#unit_price").val() === formattedPrice) {
                        getUpdateSKUFunctionality();
                    }
                }
            },
            complete: function () {
                $("#loading").fadeOut();
            },
        });
    }
}

