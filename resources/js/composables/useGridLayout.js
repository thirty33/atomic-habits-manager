export default function useGridLayout() {
    const mdColSpanClasses = {
        1: 'md:col-span-1', 2: 'md:col-span-2', 3: 'md:col-span-3',
        4: 'md:col-span-4', 5: 'md:col-span-5', 6: 'md:col-span-6',
        7: 'md:col-span-7', 8: 'md:col-span-8', 9: 'md:col-span-9',
        10: 'md:col-span-10', 11: 'md:col-span-11', 12: 'md:col-span-12',
    };

    const colSpanClasses = {
        1: 'lg:col-span-1', 2: 'lg:col-span-2', 3: 'lg:col-span-3',
        4: 'lg:col-span-4', 5: 'lg:col-span-5', 6: 'lg:col-span-6',
        7: 'lg:col-span-7', 8: 'lg:col-span-8', 9: 'lg:col-span-9',
        10: 'lg:col-span-10', 11: 'lg:col-span-11', 12: 'lg:col-span-12',
    };

    const xlColSpanClasses = {
        1: 'xl:col-span-1', 2: 'xl:col-span-2', 3: 'xl:col-span-3',
        4: 'xl:col-span-4', 5: 'xl:col-span-5', 6: 'xl:col-span-6',
        7: 'xl:col-span-7', 8: 'xl:col-span-8', 9: 'xl:col-span-9',
        10: 'xl:col-span-10', 11: 'xl:col-span-11', 12: 'xl:col-span-12',
    };

    const rowSpanClasses = {
        1: 'lg:row-span-1', 2: 'lg:row-span-2', 3: 'lg:row-span-3',
    };

    const gridClass = (field) => {
        const classes = [];
        if (field.md_col_span) classes.push(mdColSpanClasses[field.md_col_span]);
        classes.push(colSpanClasses[field.col_span] || 'lg:col-span-12');
        if (field.xl_col_span) classes.push(xlColSpanClasses[field.xl_col_span]);
        if (field.row_span) classes.push(rowSpanClasses[field.row_span] || '');
        return classes.filter(Boolean).join(' ');
    };

    return { gridClass };
}