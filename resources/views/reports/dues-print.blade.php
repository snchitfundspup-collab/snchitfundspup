{{-- Printable Pending & Due report. --}}
@extends('layouts.print')

@section('title', 'Pending & Due')

@section('doc_title', 'Pending & Due')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('reports.partials.dues-print-body')

@endsection
