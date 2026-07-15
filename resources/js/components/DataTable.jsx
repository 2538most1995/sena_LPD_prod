import React from 'react';
import { flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { Spinner } from '@fluentui/react-components';

export default function DataTable({ columns, data = [], loading, emptyTitle = 'ยังไม่มีข้อมูล', emptyText = 'เพิ่มรายการใหม่เพื่อเริ่มต้นใช้งาน' }) {
    const table = useReactTable({ data, columns, getCoreRowModel: getCoreRowModel() });

    if (loading) {
        return <div className="state-panel"><Spinner label="กำลังโหลดข้อมูล" /></div>;
    }

    if (!data.length) {
        return (
            <div className="state-panel empty-state">
                <strong>{emptyTitle}</strong>
                <span>{emptyText}</span>
            </div>
        );
    }

    return (
        <div className="table-scroll">
            <table className="data-table">
                <thead>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <tr key={headerGroup.id}>
                            {headerGroup.headers.map((header) => (
                                <th key={header.id} style={{ width: header.getSize() === 150 ? undefined : header.getSize() }}>
                                    {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                </th>
                            ))}
                        </tr>
                    ))}
                </thead>
                <tbody>
                    {table.getRowModel().rows.map((row) => (
                        <tr key={row.id}>
                            {row.getVisibleCells().map((cell) => (
                                <td key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
