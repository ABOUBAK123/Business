@extends('layouts.app')
@section('title', 'Nouveau Workflow')
@section('page-title', 'Créer un Workflow')
@section('content')

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('workflows.store') }}" id="workflowForm" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du workflow <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Ex: Validation contrat, Signature courrier...">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- Étapes -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">Étapes du workflow</label>
                    <button type="button" onclick="addStep()" class="text-indigo-600 text-xs hover:text-indigo-800 flex items-center gap-1">
                        <i class="fas fa-plus-circle"></i> Ajouter une étape
                    </button>
                </div>
                <div id="steps" class="space-y-3"></div>
                <p id="noSteps" class="text-xs text-gray-400 text-center py-4 border-2 border-dashed border-gray-200 rounded-lg">
                    Cliquez sur "Ajouter une étape" pour définir les étapes du workflow
                </p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Créer le workflow
                </button>
                <a href="{{ route('workflows.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
let stepCount = 0;
const types = {
    review: 'Révision',
    sign: 'Signature',
    approve: 'Approbation',
    reject: 'Rejet',
    notify: 'Notification'
};
const users = @json($users ?? []);

function addStep() {
    stepCount++;
    document.getElementById('noSteps').classList.add('hidden');
    const div = document.createElement('div');
    div.id = 'step-' + stepCount;
    div.className = 'bg-gray-50 rounded-lg p-4 border border-gray-200';
    div.innerHTML = `
        <div class="flex items-center gap-3">
            <span class="w-7 h-7 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">${stepCount}</span>
            <div class="grid grid-cols-3 gap-3 flex-1">
                <input type="text" name="steps[${stepCount}][name]" placeholder="Nom de l'étape"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <select name="steps[${stepCount}][type]" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    ${Object.entries(types).map(([v,l]) => `<option value="${v}">${l}</option>`).join('')}
                </select>
                <div class="relative">
                    <input type="text" placeholder="Rechercher un signataire..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 search-input" data-step-id="${stepCount}">
                    <input type="hidden" name="steps[${stepCount}][assignee_id]" class="assignee-input" value="">
                    <div class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden search-results" style="max-height: 200px; overflow-y: auto;">
                        ${users.map(u => `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-indigo-50 user-option" data-user-id="${u.id}" data-user-name="${u.name}">${u.name} <span class="text-xs text-gray-400">${u.email}</span></div>`).join('')}
                    </div>
                </div>
            </div>
            <button type="button" onclick="removeStep(${stepCount})" class="text-red-400 hover:text-red-600 p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    document.getElementById('steps').appendChild(div);

    // Ajouter les event listeners de recherche
    const searchInput = div.querySelector('.search-input');
    const searchResults = div.querySelector('.search-results');
    const userOptions = div.querySelectorAll('.user-option');
    const assigneeInput = div.querySelector('.assignee-input');

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        searchResults.classList.toggle('hidden', !query);
        userOptions.forEach(opt => {
            const name = opt.dataset.userName.toLowerCase();
            const text = opt.textContent.toLowerCase();
            opt.classList.toggle('hidden', !name.includes(query) && !text.includes(query));
        });
    });

    userOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            searchInput.value = opt.dataset.userName;
            assigneeInput.value = opt.dataset.userId;
            searchResults.classList.add('hidden');
        });
    });

    searchInput.addEventListener('blur', () => {
        setTimeout(() => searchResults.classList.add('hidden'), 100);
    });
    searchInput.addEventListener('focus', () => {
        if (searchInput.value) {
            searchResults.classList.remove('hidden');
        }
    });
}

function removeStep(n) {
    document.getElementById('step-' + n).remove();
    if (!document.getElementById('steps').children.length) {
        document.getElementById('noSteps').classList.remove('hidden');
    }
}
</script>
@endsection
