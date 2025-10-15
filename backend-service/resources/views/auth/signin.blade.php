<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .signin-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .signin-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .signin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .signin-body {
            padding: 3rem;
        }

        .btn-oauth {
            border: 2px solid;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-oauth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-google {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-google:hover {
            background-color: #dc3545;
            color: white;
        }

        .btn-microsoft {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .btn-microsoft:hover {
            background-color: #0d6efd;
            color: white;
        }

        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6c757d;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="signin-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card signin-card">
                        <div class="signin-header">
                            <h2 class="mb-0">Welcome to {{ config('app.name') }}</h2>
                            <p class="mb-0 mt-2">Sign in to your account or create a new one</p>
                        </div>
                        <div class="signin-body">
                            <div class="text-center mb-4">
                                <h4>Choose your sign-in method</h4>
                                <p class="text-muted">Connect with your preferred account</p>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="{{ route('google.login') }}"
                                    class="btn btn-oauth btn-google d-flex align-items-center justify-content-center">
                                    <svg width="20" height="20" class="me-3" viewBox="0 0 24 24">
                                        <path fill="currentColor"
                                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                        <path fill="currentColor"
                                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                        <path fill="currentColor"
                                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                        <path fill="currentColor"
                                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                    </svg>
                                    Continue with Google
                                </a>

                                <a href="{{ route('microsoft.login') }}"
                                    class="btn btn-oauth btn-microsoft d-flex align-items-center justify-content-center">
                                    <svg width="20" height="20" class="me-3" viewBox="0 0 24 24">
                                        <path fill="currentColor"
                                            d="M11.4 24H0V12.6h11.4V24zM24 24H12.6V12.6H24V24zM11.4 11.4H0V0h11.4v11.4zM24 11.4H12.6V0H24v11.4z" />
                                    </svg>
                                    Continue with Microsoft
                                </a>
                            </div>

                            <div class="divider">
                                <span>Secure & Easy Access</span>
                            </div>

                            <div class="text-center">
                                <small class="text-muted">
                                    By signing in, you agree to our terms of service and privacy policy.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
