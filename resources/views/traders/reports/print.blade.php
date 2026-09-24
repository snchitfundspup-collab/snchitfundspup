{{-- Printable SN Traders report (rice sales, profit & loss, dues, day book). --}}
@extends('layouts.print')

@section('title', $title)

@section('doc_title', $title)

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.reports.partials.print-'.$report)

@endsection
