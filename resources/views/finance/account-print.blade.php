{{-- A customer's loan statement as a printable page. --}}
@extends('layouts.print')

@section('title', 'Loan Statement')

@section('doc_title', 'Loan Statement')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('finance.partials.print-account')

@endsection
