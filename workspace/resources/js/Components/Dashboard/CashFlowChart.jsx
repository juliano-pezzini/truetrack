import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function CashFlowChart({ data }) {
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

    return (
        <ResponsiveContainer width="100%" height={300}>
            <LineChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis
                    dataKey="month"
                    tick={{ fontSize: 12 }}
                />
                <YAxis
                    tickFormatter={formatCurrency}
                    tick={{ fontSize: 12 }}
                />
                <Tooltip
                    formatter={(value) => formatCurrency(value)}
                    contentStyle={{ fontSize: 14 }}
                />
                <Legend />
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
