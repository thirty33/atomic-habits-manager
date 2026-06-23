<x-backoffice-layout>
    <daily-report-editor
        :json-url="'{{ $json_url }}'"
        :save-entries-url="'{{ $save_entries_url }}'"
        :update-report-url="'{{ $update_report_url }}'"
        :back-url="'{{ $back_url }}'"
        :atomic-ia-url="'{{ $atomic_ia_url }}'"
    />
</x-backoffice-layout>