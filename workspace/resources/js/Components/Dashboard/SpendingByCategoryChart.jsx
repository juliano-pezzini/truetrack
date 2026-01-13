import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell } from 'recharts';

export default function SpendingByCategoryChart({ data }) {
    const formatCurrency = (value) => {
        const numValue = parseFloat(value) || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(numValue);
    };

    // Generate distinct colors for any number of categories using HSL color space
    const generateColor = (index, total) => {
        const hue = (index * 360 / total) % 360;
        const saturation = 65 + (index % 3) * 10; // 65-85%
        const lightness = 50 + (index % 2) * 5; // 50-55%
        return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
    };

    // Pre-defined colors for first 16 categories for consistency
    const baseColors = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
        '#8b5cf6', '#ec4899', '#06b6d4', '#f97316',
        '#84cc16', '#14b8a6', '#a855f7', '#f43f5e',
        '#6366f1', '#eab308', '#22c55e', '#fb923c',
    ];

    const getColor = (index) => {
        return index < baseColors.length
            ? baseColors[index]
            : generateColor(index, data.length);
    };

    const chartData = data.map(item => ({
        category: item.category_name,
        amount: parseFloat(item.total_spent),
        percentage: parseFloat(item.percentage),
    }));

    return (
        <div>
            <ResponsiveContainer width="100%" height={300}>
                <BarChart data={chartData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis
                        dataKey="category"
                        tick={{ fontSize: 12 }}
                        angle={-45}
                        textAnchor="end"
                        height={80}
                    />
                    <YAxis
                        tickFormatter={formatCurrency}
                        tick={{ fontSize: 12 }}
                    />
                    <Tooltip
                        formatter={(value) => formatCurrency(value)}
                        contentStyle={{ fontSize: 14 }}
                    />
                    <Bar dataKey="amount" name="Spending">
                        {chartData.map((entry, index) => (
                            <Cell key={`cell-${index}`} fill={getColor(index)} />
                        ))}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>

            {/* Category breakdown table */}
            <div className="mt-4">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Category
                            </th>
                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                Amount
                            </th>
                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                %
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {chartData.map((item, index) => (
                            <tr key={index}>
                                <td className="px-3 py-2 text-sm text-gray-900 flex items-center">
                                    <span
                                        className="w-3 h-3 rounded-full mr-2"
                                        style={{ backgroundColor: getColor(index) }}
                                    ></span>
                                    {item.category}
                                </td>
                                <td className="px-3 py-2 text-sm text-gray-900 text-right">
                                    {formatCurrency(item.amount)}
                                </td>
                                <td className="px-3 py-2 text-sm text-gray-500 text-right">
                                    {item.percentage.toFixed(1)}%
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
