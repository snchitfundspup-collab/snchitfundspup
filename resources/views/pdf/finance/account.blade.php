@extends('pdf.layout')

@section('title', 'Loan Statement – '.$customer->name)

@section('page_margin', '10mm')

@section('font_size', '9px')

@section('doc_title', 'Loan Statement')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    @include('payments.partials.list-styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('finance.partials.print-account')

@endsection
