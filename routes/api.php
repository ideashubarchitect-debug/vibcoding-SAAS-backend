<?php

declare(strict_types=1);

/** @var \Vibeable\Backend\Router\Router $router */

use Vibeable\Backend\Middleware\Auth;
use Vibeable\Backend\Middleware\AdminOnly;
use Vibeable\Backend\Controller\AuthController;
use Vibeable\Backend\Controller\ProjectController;
use Vibeable\Backend\Controller\UserController;
use Vibeable\Backend\Controller\PlanController;
use Vibeable\Backend\Controller\SubscriptionController;
use Vibeable\Backend\Controller\AiController;
use Vibeable\Backend\Controller\AdminController;
use Vibeable\Backend\Controller\PublishController;
use Vibeable\Backend\Controller\GdprController;

$auth = [Auth::class . '::handle'];
$admin = [AdminOnly::class . '::handle'];

// Health
$router->get('/api/health', fn () => ['ok' => true, 'service' => 'vibeable-api']);

// Auth (public)
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/auth/oauth/{provider}', [AuthController::class, 'oauth']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// Auth (protected)
$router->post('/api/auth/logout', [AuthController::class, 'logout'], $auth);
$router->get('/api/auth/me', [AuthController::class, 'me'], $auth);

// User
$router->get('/api/user', [UserController::class, 'show'], $auth);
$router->patch('/api/user', [UserController::class, 'update'], $auth);
$router->get('/api/user/credits', [UserController::class, 'credits'], $auth);
$router->get('/api/user/usage', [UserController::class, 'usage'], $auth);

// Plans (public list)
$router->get('/api/plans', [PlanController::class, 'index']);
$router->get('/api/plans/{id}', [PlanController::class, 'show']);

// Subscriptions
$router->get('/api/subscriptions', [SubscriptionController::class, 'index'], $auth);
$router->post('/api/subscriptions', [SubscriptionController::class, 'create'], $auth);
$router->patch('/api/subscriptions/{id}', [SubscriptionController::class, 'update'], $auth);
$router->get('/api/subscriptions/invoices', [SubscriptionController::class, 'invoices'], $auth);

// Projects (multi-tenant)
$router->get('/api/projects', [ProjectController::class, 'index'], $auth);
$router->post('/api/projects', [ProjectController::class, 'store'], $auth);
$router->get('/api/projects/{id}', [ProjectController::class, 'show'], $auth);
$router->patch('/api/projects/{id}', [ProjectController::class, 'update'], $auth);
$router->delete('/api/projects/{id}', [ProjectController::class, 'destroy'], $auth);

// AI builder (chat, generate, edit)
$router->post('/api/projects/{id}/chat', [AiController::class, 'chat'], $auth);
$router->post('/api/projects/{id}/generate', [AiController::class, 'generate'], $auth);
$router->post('/api/projects/{id}/edit', [AiController::class, 'edit'], $auth);
$router->get('/api/projects/{id}/preview', [ProjectController::class, 'preview'], $auth);

// Publishing
$router->post('/api/projects/{id}/publish', [PublishController::class, 'publish'], $auth);
$router->get('/api/projects/{id}/domains', [PublishController::class, 'domains'], $auth);
$router->post('/api/projects/{id}/domains', [PublishController::class, 'addDomain'], $auth);

// GDPR
$router->post('/api/gdpr/export', [GdprController::class, 'export'], $auth);
$router->post('/api/gdpr/delete-account', [GdprController::class, 'deleteAccount'], $auth);

// Admin
$router->get('/api/admin/users', [AdminController::class, 'users'], $auth + $admin);
$router->get('/api/admin/plans', [AdminController::class, 'plans'], $auth + $admin);
$router->patch('/api/admin/plans/{id}', [AdminController::class, 'updatePlan'], $auth + $admin);
$router->get('/api/admin/payments', [AdminController::class, 'payments'], $auth + $admin);
$router->get('/api/admin/usage', [AdminController::class, 'usage'], $auth + $admin);
$router->get('/api/admin/ai-config', [AdminController::class, 'aiConfig'], $auth + $admin);
$router->patch('/api/admin/ai-config', [AdminController::class, 'updateAiConfig'], $auth + $admin);
$router->get('/api/admin/settings', [AdminController::class, 'settings'], $auth + $admin);
$router->patch('/api/admin/settings', [AdminController::class, 'updateSettings'], $auth + $admin);
$router->get('/api/admin/activity', [AdminController::class, 'activity'], $auth + $admin);
$router->get('/api/admin/credits', [AdminController::class, 'credits'], $auth + $admin);
$router->post('/api/admin/credits/adjust', [AdminController::class, 'adjustCredits'], $auth + $admin);

// Referral (protected)
$router->get('/api/referrals', [\Vibeable\Backend\Controller\ReferralController::class, 'index'], $auth);
$router->post('/api/referrals', [\Vibeable\Backend\Controller\ReferralController::class, 'create'], $auth);

// PayPal webhook (no auth)
$router->post('/api/webhooks/paypal', [\Vibeable\Backend\Controller\WebhookController::class, 'paypal']);
