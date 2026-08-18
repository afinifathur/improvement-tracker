@extends('layouts.app')

@section('title', 'Kaizen Tracker | Laporan Harian')

@section('content')
@include('daily-reports._entry', [
    'person' => $person,
    'date' => $date,
    'workItems' => $workItems,
    'report' => null,
    'defaultDate' => $defaultDate,
])
@endsection
