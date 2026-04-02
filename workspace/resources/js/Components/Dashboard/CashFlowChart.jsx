import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { useTheme } from '@/Components/ThemeProvider';

export default function CashFlowChart({ data }) {
    const { effectiveTheme } = useTheme();
    const isDark = effectiveTheme === 'dark';

    const formatCurrency = (value) => {
        const numValue = parseFloat(value) || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(numValue);
    };

    const formatMonth = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            year: '2-digit',
        });
    };

    const chartData = data.map(item => ({
        month: formatMonth(item.month),
        projected: parseFloat(item.net_cash_flow),
    }));

    const axisTickColor = isDark ? '#94a3b8' : '#6b7280';
    const gridColor = isDark ? '#334155' : '#d1d5db';
    const legendColor = isDark ? '#cbd5e1' : '#374151';
    const tooltipStyle = {
        backgroundColor: isDark ? '#111827' : '#ffffff',
        borderColor: isDark ? '#475569' : '#d1d5db',
        color: isDark ? '#f3f4f6' : '#111827',
        borderRadius: '0.5rem',
        fontSize: 14,
    };
    const tooltipLabelStyle = {
        color: isDark ? '#e5e7eb' : '#111827',
    };
    const tooltipItemStyle = {
        color: isDark ? '#60a5fa' : '#2563eb',
    };

    return (
        <ResponsiveContainer width="100%" height={300}>
            <LineChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                <XAxis
                    dataKey="month"
                    tick={{ fontSize: 12, fill: axisTickColor }}
                />
                <YAxis
                    tickFormatter={formatCurrency}
                    tick={{ fontSize: 12, fill: axisTickColor }}
                />
                <Tooltip
                    formatter={(value) => formatCurrency(value)}
                    contentStyle={tooltipStyle}
                    labelStyle={tooltipLabelStyle}
                    itemStyle={tooltipItemStyle}
                />
                <Legend wrapperStyle={{ color: legendColor }} />
                <Line
                    type="monotone"
                    dataKey="projected"
                    name="Projected Balance"
                    stroke="#3b82f6"
                    strokeWidth={2}
                    dot={{ fill: '#3b82f6', r: 4 }}
                    activeDot={{ r: 6 }}
                />
            </LineChart>
        </ResponsiveContainer>
    );
}
