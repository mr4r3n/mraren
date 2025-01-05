import React from 'react'
import ReactDOM from 'react-dom/client'
import './output.css'
import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import SignOut from './routes/SignOut.tsx'
import SignUp from './routes/SignUp.tsx'
import ChatBox from './routes/ChatBox.tsx'
import LogIn from './routes/LogIn.tsx'
import SecureRoute from './routes/SecureRoute.tsx'
import { AuthProvider } from './auth/AuthProvider.tsx'

const router = createBrowserRouter([
  {
    path: "/",
    element: <LogIn />,
  },
  {
    path: "/signup",
    element: <SignUp />,
  },
  {
    path: "/signout",
    element: <SignOut />,
  },
  {
    path: "/",
    element: <SecureRoute />,
    children: [
      {
        path: "/messenger",
        element: <ChatBox />,
      }
    ]
  },
]);

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <AuthProvider>
      <RouterProvider router={router} />
    </AuthProvider>
  </React.StrictMode>
);
