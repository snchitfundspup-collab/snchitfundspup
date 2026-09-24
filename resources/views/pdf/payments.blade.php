@extends('pdf.layout')

@section('title', 'Payments')

@section('page_margin', '10mm')

@section('font_size', '8.5px')

@section('doc_title', 'Payments')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    @include('payments.partials.list-styles')
@endsection

@section('content')

    @include('payments.partials.list-heading')

    @include('payments.partials.list-table')

@endsection
