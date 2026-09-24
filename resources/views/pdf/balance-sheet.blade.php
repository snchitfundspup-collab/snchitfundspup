@extends('pdf.layout')

@section('title', 'Balance Sheet')

@section('page_margin', '12mm')

@section('font_size', '9.5px')

@section('doc_title', 'Balance Sheet')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    @include('payments.partials.list-styles')
    @include('reports.partials.dues-print-styles')
    @include('expenses.partials.balance-styles')
@endsection

@section('content')

    @include('expenses.partials.balance-body')

@endsection
