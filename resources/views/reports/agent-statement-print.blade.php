{{-- Printable Agent Statement (Chit Funds or Micro Finance). --}}
@extends('layouts.print')

@section('title', __('Agent Statement').' · '.$agent->name)

@section('doc_title', __('Agent Statement'))

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('reports.partials.agent-statement-print-body')

@endsection
