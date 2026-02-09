<script>
import {defineAsyncComponent} from "vue";

const AppToastProvider = defineAsyncComponent(() => import('@/providers/AppToastProvider.vue'));
const BackofficeLayout = defineAsyncComponent(() => import('@/layouts/BackofficeLayout.vue'));
const DashboardPage = defineAsyncComponent(() => import('@/pages/backoffice/dashboard/DashboardPage.vue'));
const DatatablePage = defineAsyncComponent(() => import('@/pages/backoffice/DatatablePage.vue'));
const AtomicIaChatPage = defineAsyncComponent(() => import('@/pages/backoffice/AtomicIAChatPage.vue'));

export default {
    name: 'App',
    components: {
        AppToastProvider,
        BackofficeLayout,
        DashboardPage,
        DatatablePage,
        AtomicIaChatPage,
    },
    setup() {
        return {

        }
    }
}
</script>
