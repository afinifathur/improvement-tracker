@extends('layouts.app')

@section('title', 'Kaizen Tracker | Ubah Laporan Harian')

@section('content')
@include('daily-reports._entry', [
    'person' => $person,
    'date' => $date,
    'workItems' => $workItems,
    'report' => $report,
    'defaultDate' => $defaultDate,
])
@endsection
