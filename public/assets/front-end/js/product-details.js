"use strict";

$(document).ready(function () {
    const $stickyElement = $(".bottom-sticky");
    const $offsetElement = $(".product-details-shipping-details");

    $(window).on("scroll", function () {
        const elementOffset = $offsetElement.offset().top;
        const scrollTop = $(window).scrollTop();

        if (scrollTop >= elementOffset) {
            $stickyElement.addClass("stick");
            $(".floating-btn-grp").removeClass("style-2");
        } else {
            $stickyElement.removeClass("stick");
            $(".floating-btn-grp").addClass("style-2");
        }
    });
});

cartQuantityInitialize();
getVariantPrice();

$(".view_more_button").on("click", function () {
    loadReviewOnDetailsPage();
});

let loadReviewCount = 1;

function loadReviewOnDetailsPage() {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        type: "post",
        url: $("#route-review-list-product").data("url"),
        data: {
            product_id: $("#products-details-page-data").data("id"),
            offset: loadReviewCount,
        },
        success: function (data) {
            $("#product-review-list").append(data.productReview);
            if (data.checkReviews == 0) {
                $(".view_more_button").removeClass("d-none").addClass("d-none");
            } else {
                $(".view_more_button").addClass("d-none").removeClass("d-none");
            }

            $(".show-instant-image").on("click", function () {
                let link = $(this).data("link");
                showInstantImage(link);
            });
        },
    });
    loadReviewCount++;
}

$("#chat-form").on("submit", function (e) {
    e.preventDefault();

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        type: "post",
        url: $("#route-messages-store").data("url"),
        data: $("#chat-form").serialize(),
        success: function (respons) {
            toastr.success($("#message-send-successfully").data("text"), {
                CloseButton: true,
                ProgressBar: true,
            });
            $("#chat-form").trigger("reset");
        },
    });
});

function renderFocusPreviewImageByColor() {
    $(".focus-preview-image-by-color").on("click", function () {
        let id = $(this).data("colorid");
        $(`.color-variants-${id}`).click();
    });
}
renderFocusPreviewImageByColor();

$('.variant-radio').on('change', function() {
    var selectedSize = $(this).val();
    $.ajax({
        url: $("#route-variant-product-size-price").data("url"), // change this URL to your route
        type: 'POST',
        data: {
            size: selectedSize,
            product_id: $("#products-details-page-data").data("id"),
        },
        beforeSend: function () {
            $('#loading').fadeIn();
        },
        success: function(response) {
            $("#chosen_price").text(response.price);
            $(".discounted_unit_price").text(response.price);
            $(".unit_price").text(response.price);
            $(".unit_price").attr("data-price", response.price);
            $(".unit_price").attr("data-price-variant", response.price);
            
        },
        error: function(xhr) {
            console.log('Error:', xhr.responseText);
        },
        complete: function () {
            $('#loading').fadeOut();
        },
    });
});