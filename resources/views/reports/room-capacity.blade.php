@extends('layouts.admin')

@section('title', 'Room capacity')

@section('content')
    <div class="border-bottom mb-3 pt-3 pb-2">
        <h1 class="h2">{{ $event->name }}</h1>
        <span class="h6">{{ $event->date?->format('F j, Y') }}</span>
    </div>

    <div class="mb-3 pt-3 pb-2">
        <h2 class="h4">Room Capacity</h2>
    </div>

    @if ($rows->isEmpty())
        <div class="alert alert-light border">This event has no sessions yet.</div>
    @else
        <div class="card shadow-sm">
            <div class="card-body">
                <canvas id="room-capacity-chart" height="130"></canvas>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($rows->isNotEmpty())
        <script src="{{ asset('assets/vendor/Chart.bundle.min.js') }}"></script>
        <script>
            (function () {
                var rows = @json($rows);
                var context = document.getElementById('room-capacity-chart');

                new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: rows.map(function (row) { return row.title; }),
                        datasets: [
                            {
                                label: 'Attendees',
                                data: rows.map(function (row) { return row.attendees; }),
                                backgroundColor: rows.map(function (row) {
                                    return row.exceeded ? 'rgba(220, 53, 69, .75)' : 'rgba(146, 197, 120, .8)';
                                }),
                                borderColor: rows.map(function (row) {
                                    return row.exceeded ? 'rgba(220, 53, 69, 1)' : 'rgba(120, 170, 95, 1)';
                                }),
                                borderWidth: 1
                            },
                            {
                                label: 'Capacity',
                                data: rows.map(function (row) { return row.capacity; }),
                                backgroundColor: 'rgba(116, 172, 224, .55)',
                                borderColor: 'rgba(80, 140, 200, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            xAxes: [{
                                ticks: { autoSkip: false, maxRotation: 60, minRotation: 45 }
                            }],
                            yAxes: [{
                                ticks: { beginAtZero: true, precision: 0 }
                            }]
                        }
                    }
                });
            })();
        </script>
    @endif
@endpush
