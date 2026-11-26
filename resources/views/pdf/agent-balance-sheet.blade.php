{{-- Agent Balance Sheet PDF (Micro Finance). --}}
@extends('pdf.layout')

@section('title', __('Agent Balance Sheet').' · '.$agent?->name)

@section('page_margin', '10mm')

@section('font_size', '8.5px')

@section('doc_title', __('Agent Balance Sheet'))

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    @include('payments.partials.list-styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('reports.partials.agent-balance-sheet-print-body')

@endsection
