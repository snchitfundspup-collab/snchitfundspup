{{-- Printable payment list: the same letterhead and table as the PDF. --}}
@extends('layouts.print')

@section('title', 'Payments')

@section('doc_title', 'Payments')

@section('content')

    @include('payments.partials.list-heading')

    @include('payments.partials.list-table')

@endsection
