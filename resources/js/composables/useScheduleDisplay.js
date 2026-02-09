export default function useScheduleDisplay() {
    const dayLabels = {
        0: 'Dom', 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb',
    };

    const getExtraInfo = (data) => {
        if (!data) return null;

        if (data.days_of_week?.length) {
            return data.days_of_week.map(d => dayLabels[d] || d).join(', ');
        }

        if (data.interval_days) {
            return `Cada ${data.interval_days} días`;
        }

        if (data.specific_date) {
            return data.specific_date;
        }

        return null;
    };

    return { dayLabels, getExtraInfo };
}
