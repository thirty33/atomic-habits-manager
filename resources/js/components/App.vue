<script>
import {defineAsyncComponent} from "vue";

const AppToastProvider = defineAsyncComponent(() => import('@/providers/AppToastProvider.vue'));
const BackofficeLayout = defineAsyncComponent(() => import('@/layouts/BackofficeLayout.vue'));
const DashboardPage = defineAsyncComponent(() => import('@/pages/backoffice/dashboard/DashboardPage.vue'));
const DatatablePage = defineAsyncComponent(() => import('@/pages/backoffice/DatatablePage.vue'));
const AtomicIaChatPage = defineAsyncComponent(() => import('@/pages/backoffice/AtomicIAChatPage.vue'));
const CalendarPage = defineAsyncComponent(() => import('@/pages/backoffice/CalendarPage.vue'));
const DailyReportsPage = defineAsyncComponent(() => import('@/pages/backoffice/DailyReportsPage.vue'));
const DailyReportPage = defineAsyncComponent(() => import('@/pages/backoffice/DailyReportPage.vue'));

export default {
    name: 'App',
    components: {
        AppToastProvider,
        BackofficeLayout,
        DashboardPage,
        DatatablePage,
        AtomicIaChatPage,
        CalendarPage,
        DailyReportsPage,
        DailyReportPage,
    },
    setup() {
        return {

        }
    }
}
</script>
