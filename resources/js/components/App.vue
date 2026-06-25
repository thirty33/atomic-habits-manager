<script>
import {defineAsyncComponent} from "vue";

const AppToastProvider = defineAsyncComponent(() => import('@/providers/AppToastProvider.vue'));
const BackofficeLayout = defineAsyncComponent(() => import('@/layouts/BackofficeLayout.vue'));
const DashboardPage = defineAsyncComponent(() => import('@/pages/backoffice/dashboard/DashboardPage.vue'));
const DatatablePage = defineAsyncComponent(() => import('@/pages/backoffice/DatatablePage.vue'));
const AtomicIaChatPage = defineAsyncComponent(() => import('@/pages/backoffice/AtomicIAChatPage.vue'));
const CalendarPage = defineAsyncComponent(() => import('@/pages/backoffice/CalendarPage.vue'));
const CalendarBoard = defineAsyncComponent(() => import('@/pages/backoffice/CalendarBoard.vue'));
const DailyReportsPage = defineAsyncComponent(() => import('@/pages/backoffice/DailyReportsPage.vue'));
const DailyReportsBoard = defineAsyncComponent(() => import('@/pages/backoffice/DailyReportsBoard.vue'));
const DailyReportPage = defineAsyncComponent(() => import('@/pages/backoffice/DailyReportPage.vue'));
const DailyReportEditor = defineAsyncComponent(() => import('@/pages/backoffice/DailyReportEditor.vue'));
const PlansPage = defineAsyncComponent(() => import('@/pages/subscriptions/PlansPage.vue'));

export default {
    name: 'App',
    components: {
        AppToastProvider,
        BackofficeLayout,
        DashboardPage,
        DatatablePage,
        AtomicIaChatPage,
        CalendarPage,
        CalendarBoard,
        DailyReportsPage,
        DailyReportsBoard,
        DailyReportPage,
        DailyReportEditor,
        PlansPage,
    },
    setup() {
        return {

        }
    }
}
</script>
