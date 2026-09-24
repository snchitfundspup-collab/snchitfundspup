{{-- Printable balance sheet between the partners. --}}
@extends('layouts.print')

@section('title', 'Balance Sheet')

@section('doc_title', 'Balance Sheet')

@section('styles')
    @include('reports.partials.dues-print-styles')
    @include('expenses.partials.balance-styles')
@endsection

@section('content')

    @include('expenses.partials.balance-body')

@endsection
