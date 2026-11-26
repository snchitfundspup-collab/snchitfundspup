{{-- Printable Agent Balance Sheet (Micro Finance). --}}
@extends('layouts.print')

@section('title', __('Agent Balance Sheet').' · '.$agent?->name)

@section('doc_title', __('Agent Balance Sheet'))

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('reports.partials.agent-balance-sheet-print-body')

@endsection
