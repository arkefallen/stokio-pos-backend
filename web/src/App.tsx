import { BrowserRouter, Route, Routes, Navigate } from 'react-router-dom'
import LoginPage from '@/features/auth/login-page'
import { useAuthStore } from '@/stores/use-auth-store'
import DashboardLayout from '@/layouts/dashboard-layout'

import DashboardOverview from '@/features/dashboard/dashboard-overview'

// Placeholder Components (Screens)
// DashboardOverview removed
import ProductList from '@/features/catalog/product-list'

// Placeholder Components (Screens)
import ProductForm from '@/features/catalog/product-form'

import PurchasingDashboard from '@/features/purchasing/purchasing-dashboard'
import PurchaseOrderForm from '@/features/purchasing/purchase-order-form'

// Placeholder Components (Screens)
// ProductList removed
// PurchasingDashboard removed
// PurchasingDashboard removed
// InventoryDashboard removed
import StockOpname from '@/features/inventory/stock-opname'
import SalesHistory from '@/features/sales/sales-history'
import UserList from '@/features/users/user-list'

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/*" element={<ProtectedRoutes />} />
      </Routes>
    </BrowserRouter>
  )
}

function ProtectedRoutes() {
  const { isAuthenticated } = useAuthStore()

  if (!isAuthenticated()) {
    return <Navigate to="/login" replace />
  }

  return (
    <Routes>
      <Route element={<DashboardLayout />}>
        <Route path="/" element={<DashboardOverview />} />
        <Route path="/products" element={<ProductList />} />
        <Route path="/products/create" element={<ProductForm />} />
        <Route path="/products/:id/edit" element={<ProductForm />} />

        <Route path="/purchasing" element={<PurchasingDashboard />} />
        <Route path="/purchase-orders/create" element={<PurchaseOrderForm />} />
        <Route path="/inventory" element={<StockOpname />} />
        <Route path="/sales" element={<SalesHistory />} />
        <Route path="/users" element={<UserList />} />
      </Route>
    </Routes>
  )
}

export default App
