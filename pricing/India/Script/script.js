$(document).ready(function () {
    $.getJSON("./Data/websiteData.json", function (data) {
        $.each(data, function (i, item) {
            var task = item.task;
            var Hours = item.Hours;
            var Charges = item.Charges;
            $(".websiteTable").append(
                "<tr><td>" +
                    task +
                    "</td><td><input type='number'></td><td>" +
                    Hours +
                    "</td><td>" +
                    Charges +
                    "</td><td class='totalSales'></td></tr>"
            );
        });
        $(".websiteTable").on("input", "input[type='number']", function () {
            var $row = $(this).closest("tr");
            var qty = $(this).val();
            var Hours = $row.find("td:eq(2)").text();
            var Charges = $row.find("td:eq(3)").text();
            var totalSales = "INR " + qty * Hours * Charges;
            $row.find(".totalSales").text(totalSales);
        });
    });

    $.getJSON("./Data/pwaData.json", function (data) {
        $.each(data, function (i, item) {
            var task = item.task;
            var Hours = item.Hours;
            var Charges = item.Charges;
            $(".pwaTable").append(
                "<tr><td>" +
                    task +
                    "</td><td><input type='number'></td><td>" +
                    Hours +
                    "</td><td>" +
                    Charges +
                    "</td><td class='totalSales'></td></tr>"
            );
        });
        $(".pwaTable").on("input", "input[type='number']", function () {
            var $row = $(this).closest("tr");
            var qty = $(this).val();
            var Hours = $row.find("td:eq(2)").text();
            var Charges = $row.find("td:eq(3)").text();
            var totalSales = "INR " + qty * Hours * Charges;
            $row.find(".totalSales").text(totalSales);
        });
    });

    $.getJSON("./Data/mobileData.json", function (data) {
        $.each(data, function (i, item) {
            var task = item.task;
            var Hours = item.Hours;
            var Charges = item.Charges;
            $(".mobileTable").append(
                "<tr><td>" +
                    task +
                    "</td><td><input type='number'></td><td>" +
                    Hours +
                    "</td><td>" +
                    Charges +
                    "</td><td class='totalSales'></td></tr>"
            );
        });
        $(".mobileTable").on("input", "input[type='number']", function () {
            var $row = $(this).closest("tr");
            var qty = $(this).val();
            var Hours = $row.find("td:eq(2)").text();
            var Charges = $row.find("td:eq(3)").text();
            var totalSales = "INR " + qty * Hours * Charges;
            $row.find(".totalSales").text(totalSales);
        });
    });

    $.getJSON("./Data/uiData.json", function (data) {
        $.each(data, function (i, item) {
            var task = item.task;
            var Hours = item.Hours;
            var Charges = item.Charges;
            $(".uiTable").append(
                "<tr><td>" +
                    task +
                    "</td><td><input type='number'></td><td>" +
                    Hours +
                    "</td><td>" +
                    Charges +
                    "</td><td class='totalSales'></td></tr>"
            );
        });
        $(".uiTable").on("input", "input[type='number']", function () {
            var $row = $(this).closest("tr");
            var qty = $(this).val();
            var Hours = $row.find("td:eq(2)").text();
            var Charges = $row.find("td:eq(3)").text();
            var totalSales = "INR " + qty * Hours * Charges;
            $row.find(".totalSales").text(totalSales);
        });
    });

    $.getJSON("./Data/graphicData.json", function (data) {
        $.each(data, function (i, item) {
            var task = item.task;
            var Hours = item.Hours;
            var Charges = item.Charges;
            $(".graphicTable").append(
                "<tr><td>" +
                    task +
                    "</td><td><input type='number'></td><td>" +
                    Hours +
                    "</td><td>" +
                    Charges +
                    "</td><td class='totalSales'></td></tr>"
            );
        });
        $(".graphicTable").on("input", "input[type='number']", function () {
            var $row = $(this).closest("tr");
            var qty = $(this).val();
            var Hours = $row.find("td:eq(2)").text();
            var Charges = $row.find("td:eq(3)").text();
            var totalSales = "INR " + qty * Hours * Charges;
            $row.find(".totalSales").text(totalSales);
        });
    });

    $.getJSON("./Data/hostingData.json", function (data) {
        $.each(data, function (i, item) {
            var task = item.task;
            var Months = item.Months;
            var Charges = item.Charges;
            $(".hostingTable").append(
                "<tr><td>" +
                    task +
                    "</td><td><input type='number'></td><td>" +
                    Months +
                    "</td><td>" +
                    Charges +
                    "</td><td class='totalSales'></td></tr>"
            );
        });
        $(".hostingTable").on("input", "input[type='number']", function () {
            var $row = $(this).closest("tr");
            var qty = $(this).val();
            var Months = $row.find("td:eq(2)").text();
            var Charges = $row.find("td:eq(3)").text();
            var totalSales = "INR " + qty * Months * Charges;
            $row.find(".totalSales").text(totalSales);
        });
    });
});

$(document).ready(function () {
    var total = 0;

    $("table").on("input", "input[type='number']", function () {
        var $row = $(this).closest("tr");
        var qty = $(this).val();
        var Hours = $row.find("td:eq(2)").text();
        var Charges = $row.find("td:eq(3)").text();
        var totalSales = qty * Hours * Charges;

        $row.find(".totalSales").text("INR " + totalSales);
        updateTotal();
    });

    $("table1").on("input", "input[type='number']", function () {
        var $row = $(this).closest("tr");
        var qty = $(this).val();
        var Hours = $row.find("td:eq(2)").text();
        var Charges = $row.find("td:eq(3)").text();
        var totalSales = qty * Hours * Charges;

        $row.find(".totalSales").text("INR " + totalSales);
        updateTotal();
    });

    function updateTotal() {
        var total = 0;
        var salesCells = $(".totalSales");

        salesCells.each(function () {
            var sales = $(this).text().replace("INR ", "");
            if (sales) {
                total += parseFloat(sales);
            }
        });

        $("#total").text("Total: INR " + total.toFixed(2));
    }

    updateTotal(); // Call updateTotal() initially to calculate and display the total
});
