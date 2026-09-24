@extends('pdf.layout')

@section('title', 'Sales')

@section('page_margin', '10mm')

@section('font_size', '8.5px')

@section('doc_title', 'Sales')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    @include('payments.partials.list-styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.partials.print-bills', ['kind' => 'sale', 'bills' => $sales])

@endsection
