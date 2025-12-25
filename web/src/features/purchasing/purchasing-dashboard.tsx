import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import SupplierList from "./supplier-list"
import PurchaseOrderList from "./purchase-order-list"

export default function PurchasingDashboard() {
    return (
        <div className="space-y-6">
            <h1 className="text-3xl font-bold tracking-tight">Purchasing</h1>
            <Tabs defaultValue="orders" className="w-full">
                <TabsList className="grid w-full grid-cols-2 max-w-[400px]">
                    <TabsTrigger value="orders">Purchase Orders</TabsTrigger>
                    <TabsTrigger value="suppliers">Suppliers</TabsTrigger>
                </TabsList>
                <TabsContent value="orders" className="mt-6">
                    <PurchaseOrderList />
                </TabsContent>
                <TabsContent value="suppliers" className="mt-6">
                    <SupplierList />
                </TabsContent>
            </Tabs>
        </div>
    )
}
