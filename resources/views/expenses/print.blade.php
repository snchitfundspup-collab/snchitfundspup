{{-- Printable expense list. --}}
@extends('layouts.print')

@section('title', 'Expenses')

@section('doc_title', 'Expenses')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('expenses.partials.list-body')

@endsection
