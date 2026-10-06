{{-- Printable Sri Lakshmi Micro Finance report. --}}
@extends('layouts.print')

@section('title', $title)

@section('doc_title', $title)

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('finance.reports.partials.print-'.$report)

@endsection
