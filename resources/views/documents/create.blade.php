@extends('layouts.app')
@section('title', 'Nouveau Document')
@section('page-title', 'Nouveau Document')
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre du document <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fichier <span class="text-red-500">*</span></label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2 block"></i>
                    <p class="text-sm text-gray-500 mb-2">Glisser-déposer ou cliquer pour sélectionner</p>
                    <p class="text-xs text-gray-400">PDF, DOCX, XLSX, PPTX — max 50 Mo</p>
                    <input type="file" name="file" required accept=".pdf,.docx,.xlsx,.pptx"
                           class="mt-3 block mx-auto text-sm text-gray-500">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-upload mr-2"></i> Uploader le document
                </button>
                <a href="{{ route('documents.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
