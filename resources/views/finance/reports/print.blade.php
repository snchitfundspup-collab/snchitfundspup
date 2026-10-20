{{-- Printable Sri Lakshmi Micro Finance report. --}}
@extends('layouts.print')

@section('title', $title)

@section('doc_title', $title)

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @if ($agentName ?? null)
        <p class="list-title">{{ __('Agent') }}: {{ $agentName }}</p>
    @endif

    @include('finance.reports.partials.print-'.$report)

@endsection
