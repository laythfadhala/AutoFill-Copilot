@extends('layouts.app')

@section('title', 'Test Form - AutoFill Copilot')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0">
                            <i class="bi bi-clipboard-check"></i>
                            AutoFill Copilot - Form Test Page
                        </h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Testing Instructions</h5>
                            <ol class="mb-0">
                                <li>Make sure the backend is running (<code>docker-compose up -d</code>)</li>
                                <li>Load the extension in Chrome (Developer mode)</li>
                                <li>Log in to the extension</li>
                                <li>Click <strong>"Fill Current Form"</strong> in the extension popup</li>
                                <li>Watch as AI fills all the forms below using your profile data!</li>
                            </ol>
                            <p class="mb-0 mt-2"><strong>Note:</strong> If you don't have a default active profile, the
                                system will generate realistic sample data instead.</p>
                        </div>

                        <!-- Personal Information Form -->
                        <form id="personal-form" action="/submit-personal" method="POST" class="mb-4">
                            <h3 class="h5 mb-3 text-primary">
                                <i class="bi bi-person"></i>
                                Personal Information
                            </h3>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        placeholder="Enter your first name">
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        placeholder="Enter your last name">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="your.email@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="(555) 123-4567">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label for="birthdate" class="form-label">Birth Date</label>
                                    <input type="date" class="form-control" id="birthdate" name="birthdate">
                                </div>
                                <div class="col-md-6">
                                    <label for="occupation" class="form-label">Occupation</label>
                                    <input type="text" class="form-control" id="occupation" name="occupation"
                                        placeholder="Your job title">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Submit Personal Info</button>
                        </form>

                        <!-- Address Information Form -->
                        <form id="address-form" action="/submit-address" method="POST" class="mb-4">
                            <h3 class="h5 mb-3 text-success">
                                <i class="bi bi-house"></i>
                                Address Information
                            </h3>

                            <div class="mb-3">
                                <label for="street_address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="street_address" name="street_address"
                                    placeholder="123 Main Street">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                        placeholder="City name">
                                </div>
                                <div class="col-md-4">
                                    <label for="state" class="form-label">State/Province</label>
                                    <select class="form-select" id="state" name="state">
                                        <option value="">Select a state</option>
                                        <option value="CA">California</option>
                                        <option value="NY">New York</option>
                                        <option value="TX">Texas</option>
                                        <option value="FL">Florida</option>
                                        <option value="IL">Illinois</option>
                                        <option value="WA">Washington</option>
                                        <option value="CO">Colorado</option>
                                        <option value="AZ">Arizona</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code"
                                        placeholder="12345">
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Select a country</option>
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="UK">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                    <option value="DE">Germany</option>
                                    <option value="FR">France</option>
                                    <option value="JP">Japan</option>
                                    <option value="BR">Brazil</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success">Submit Address</button>
                        </form>

                        <!-- Preferences Form -->
                        <form id="preferences-form" action="/submit-preferences" method="POST" class="mb-4">
                            <h3 class="h5 mb-3 text-warning">
                                <i class="bi bi-sliders"></i>
                                Preferences & Feedback
                            </h3>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="favorite_color" class="form-label">Favorite Color</label>
                                    <select class="form-select" id="favorite_color" name="favorite_color">
                                        <option value="">Choose a color</option>
                                        <option value="red">Red</option>
                                        <option value="blue">Blue</option>
                                        <option value="green">Green</option>
                                        <option value="yellow">Yellow</option>
                                        <option value="purple">Purple</option>
                                        <option value="orange">Orange</option>
                                        <option value="pink">Pink</option>
                                        <option value="black">Black</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="experience_level" class="form-label">Experience Level</label>
                                    <select class="form-select" id="experience_level" name="experience_level">
                                        <option value="">Select level</option>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                        <option value="expert">Expert</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="comments" class="form-label">Comments or Feedback</label>
                                <textarea class="form-control" id="comments" name="comments" rows="4"
                                    placeholder="Tell us your thoughts..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Preferred Contact Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_method"
                                        id="contact_email" value="email">
                                    <label class="form-check-label" for="contact_email">
                                        Email
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_method"
                                        id="contact_phone" value="phone">
                                    <label class="form-check-label" for="contact_phone">
                                        Phone
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_method"
                                        id="contact_text" value="text">
                                    <label class="form-check-label" for="contact_text">
                                        Text Message
                                    </label>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter"
                                    value="1">
                                <label class="form-check-label" for="newsletter">
                                    Subscribe to newsletter
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms"
                                    value="1">
                                <label class="form-check-label" for="terms">
                                    I agree to the terms and conditions
                                </label>
                            </div>

                            <button type="submit" class="btn btn-warning">Submit Preferences</button>
                        </form>

                        <!-- Account Information Form -->
                        <form id="account-form" action="/submit-account" method="POST">
                            <h3 class="h5 mb-3 text-danger">
                                <i class="bi bi-shield-lock"></i>
                                Account Information
                            </h3>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        placeholder="Choose a username">
                                </div>
                                <div class="col-md-6">
                                    <label for="account_type" class="form-label">Account Type</label>
                                    <select class="form-select" id="account_type" name="account_type">
                                        <option value="">Select account type</option>
                                        <option value="personal">Personal</option>
                                        <option value="business">Business</option>
                                        <option value="premium">Premium</option>
                                        <option value="enterprise">Enterprise</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Enter password">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" placeholder="Confirm password">
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="security_question" class="form-label">Security Question</label>
                                <select class="form-select" id="security_question" name="security_question">
                                    <option value="">Select a security question</option>
                                    <option value="pet">What was the name of your first pet?</option>
                                    <option value="school">What was the name of your first school?</option>
                                    <option value="city">In what city were you born?</option>
                                    <option value="mother">What is your mother's maiden name?</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="security_answer" class="form-label">Security Answer</label>
                                <input type="text" class="form-control" id="security_answer" name="security_answer"
                                    placeholder="Your answer">
                            </div>

                            <button type="submit" class="btn btn-danger">Create Account</button>
                        </form>

                        <div class="mt-4 p-3 bg-light rounded">
                            <h4 class="h6 mb-2">🎯 What to Expect</h4>
                            <p class="mb-1">The AI will generate realistic data for all form fields:</p>
                            <ul class="mb-0 small">
                                <li><strong>Names:</strong> Realistic first and last names</li>
                                <li><strong>Email/Phone:</strong> Properly formatted contact info</li>
                                <li><strong>Addresses:</strong> Complete street addresses</li>
                                <li><strong>Dates:</strong> Appropriate date formats</li>
                                <li><strong>Select dropdowns:</strong> Choose from available options</li>
                                <li><strong>Text areas:</strong> Meaningful sample content</li>
                                <li><strong>Checkboxes/Radio:</strong> Appropriate selections</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .card {
            border: none;
            border-radius: 10px;
        }

        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }

        .btn {
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-info {
            border-left: 4px solid #0dcaf0;
        }

        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
    </style>
@endsection
