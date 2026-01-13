import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import PeriodSummaryCard from '@/Components/Dashboard/PeriodSummaryCard';
import CashFlowChart from '@/Components/Dashboard/CashFlowChart';
import SpendingByCategoryChart from '@/Components/Dashboard/SpendingByCategoryChart';
import InvestmentSummary from '@/Components/Dashboard/InvestmentSummary';
import AlertsPanel from '@/Components/Dashboard/AlertsPanel';

export default function Dashboard({
    period,
    periodSummary,
    cashFlowProjection,
    spendingByCategory,
    investmentReturns,
    alerts
}) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Alerts Panel */}
                    {alerts && alerts.total_count > 0 && (
                        <div className="mb-6">
                            <AlertsPanel alerts={alerts} />
                        </div>
                    )}

                    {/* Period Summary */}
                    {periodSummary && (
                        <div className="mb-6">
                            <PeriodSummaryCard
                                period={period}
                                summary={periodSummary}
                            />
                        </div>
                    )}

                    {/* Charts Grid */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                        {/* Cash Flow Projection */}
                        {cashFlowProjection && cashFlowProjection.length > 0 && (
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-semibold mb-4">Cash Flow Projection</h3>
                                    <CashFlowChart data={cashFlowProjection} />
                                </div>
                            </div>
                        )}

                        {/* Spending by Category */}
                        {spendingByCategory && spendingByCategory.length > 0 && (
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-semibold mb-4">Spending by Category</h3>
                                    <SpendingByCategoryChart data={spendingByCategory} />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Investment Summary */}
                    {investmentReturns && (Array.isArray(investmentReturns) ? investmentReturns.length > 0 : true) && (
                        <div>
                            <InvestmentSummary returns={investmentReturns} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
