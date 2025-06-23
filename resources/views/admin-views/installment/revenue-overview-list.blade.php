@extends('layouts.back-end.app')

@section('title', 'Revenue Overview')

@section('content')
<style>
    .option-select-btn .earn-statistics {
        padding: 8px 16px;
        border: 1px solid #007bff;
        border-radius: 5px;
        transition: background-color 0.3s, color 0.3s;
        color: #007bff;
        font-weight: 500;
        cursor: pointer;
    }

    .apexcharts-toolbar {
        display: none;
    }

    .option-select-btn .earn-statistics.active {
        background-color: #007bff;
        color: #fff;
    }

    .card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .option-select-btn label {
        cursor: pointer;
    }

    .option-select-btn .earn-statistics {
        padding: 8px 16px;
        border: 1px solid #007bff;
        border-radius: 5px;
        transition: background-color 0.3s, color 0.3s;
        color: #007bff;
        font-weight: 500;
    }

    .option-select-btn input:checked+.earn-statistics {
        background-color: #007bff;
        color: #fff;
    }
</style>

<div class="content container-fluid">

    <div class="card p-3 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h4 class="d-flex align-items-center text-capitalize gap-2 mb-0">
                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/order-statistics.png') }}" alt="">
                    Revenue Overview
                </h4>
            </div>
            <div class="col-md-6 d-flex justify-content-center justify-content-md-end order-stat">
                <ul class="option-select-btn earn-statistics-option list-unstyled d-flex gap-2 mb-0">
                    <li>
                        <label class="basic-box-shadow">
                            <input type="radio" name="statistics" hidden value="daily"
                                onchange="updateChart('daily')" />
                            <span data-date-type="daily" class="earn-statistics">Daily</span>
                        </label>
                    </li>
                    <li>
                        <label class="basic-box-shadow">
                            <input type="radio" name="statistics" hidden value="monthly"
                                onchange="updateChart('monthly')" />
                            <span data-date-type="monthly" class="earn-statistics active">Monthly</span>
                        </label>
                    </li>
                    <li>
                        <label class="basic-box-shadow">
                            <input type="radio" name="statistics" hidden value="yearly"
                                onchange="updateChart('yearly')" />
                            <span data-date-type="yearly" class="earn-statistics">Yearly</span>
                        </label>
                    </li>
                </ul>
            </div>

        </div>

        <div id="chart" class="mt-3">
            <div id="user-profit-chart" style="height: 350px;"></div>
        </div>
    </div>

</div>

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/apexcharts.js') }}"></script>
<script>
    let chart; // Declare chart variable outside functions
    $(document).ready(function() {
        initializeChart(@json($installments), "monthly");
        setActiveButton("monthly"); // Automatically select the monthly button
    });

    function initializeChart(data, type) {
        let categories = [],
            profitData = [];
        data.forEach(item => {
            categories.push(item.date);
            profitData.push(item.total_profit);
        });
        var options = {
            chart: {
                type: 'line',
                height: 350,
                id: 'userProfitChart'
            },
            series: [{
                name: 'Profit',
                data: profitData
            }],
            xaxis: {
                categories: categories
            },
            colors: ['#4CAF50'],
            stroke: {
                curve: 'smooth',
                width: 3
            },
        };
        // Initialize chart only once
        chart = new ApexCharts(document.querySelector("#user-profit-chart"), options);
        chart.render();
    }

    function updateChart(type) {
        setActiveButton(type);
        $.ajax({
            url: "{{ route('admin.paymentRequests.revenueOverview') }}?type=" + type,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                let categories = [],
                    profitData = [];
                data.forEach(item => {
                    categories.push(item.date);
                    profitData.push(item.total_profit);
                });
                // Update existing chart instead of creating a new one
                chart.updateOptions({
                    xaxis: {
                        categories: categories
                    }
                });
                chart.updateSeries([{
                    name: 'Profit',
                    data: profitData
                }]);
            },
            error: function(xhr, status, error) {
                console.error("Error fetching data:", error);
            }
        });
    }

    function setActiveButton(type) {
        // Use jQuery to toggle active class
        $('.earn-statistics').removeClass('active');
        $(`.earn-statistics[data-date-type="${type}"]`).addClass('active');
    }
</script>

@endpush
@endsection