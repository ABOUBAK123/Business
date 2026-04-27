<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Visualiseur</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #1e293b; }
        #native-viewer { width: 100%; height: 100vh; border: none; display: block; }
        #oo-editor     { width: 100%; height: 100vh; }
    </style>
</head>
<body>

@if($ooViewer === 'onlyoffice' && $ooUrl)
{{-- ── OnlyOffice Document Server viewer ────────────────── --}}
<div id="oo-editor"></div>
<script src="{{ $ooUrl }}/web-apps/apps/api/documents/api.js"></script>
<script>
(function () {
    var fileUrl  = @json($fileUrl);
    var docTitle = @json($title);
    var ext      = fileUrl.split('.').pop().toLowerCase();
    var typeMap  = {
        pdf: 'pdf', docx: 'word', doc: 'word', odt: 'word',
        xlsx: 'cell', xls: 'cell', ods: 'cell',
        pptx: 'slide', ppt: 'slide', odp: 'slide',
    };
    var config = {
        document: {
            fileType: ext,
            key:      'courrier_' + Math.abs(fileUrl.split('').reduce(function(a,c){return a+c.charCodeAt(0)},0)),
            title:    docTitle,
            url:      fileUrl,
            permissions: { edit: false, download: true, print: true },
        },
        documentType: typeMap[ext] || 'word',
        editorConfig: {
            mode: 'view',
            lang: 'fr',
            customization: {
                autosave: false, chat: false, comments: false,
                compactHeader: true, toolbarNoTabs: true,
            },
        },
        @if($ooJwt)
        token: @json($ooJwt),
        @endif
        type: 'desktop',
    };
    new DocsAPI.DocEditor('oo-editor', config);
})();
</script>

@else
{{-- ── Lecteur natif (iframe navigateur) ────────────────── --}}
<iframe id="native-viewer"
    src="{{ $fileUrl }}"
    title="{{ $title }}"
    allowfullscreen>
</iframe>
@endif

</body>
</html>
