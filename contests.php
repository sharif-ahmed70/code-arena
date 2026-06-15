<?php
// ============================================================
//  CODE ARENA - Contest Tracker
// ============================================================
require_once 'includes/session.php';

$username = currentUsername() ?? 'Guest';
$initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contest Tracker - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        :root {
            --ca-bg: #0b0f17;
            --ca-bg-2: #101622;
            --ca-glass: rgba(18, 24, 36, .72);
            --ca-glass-strong: rgba(22, 30, 45, .9);
            --ca-line: rgba(255, 255, 255, .09);
            --ca-line-strong: rgba(255, 255, 255, .14);
            --ca-text: #f7f9fc;
            --ca-muted: #a2acbd;
            --ca-dim: #687386;
            --ca-green: #35e59b;
            --ca-blue: #67a7ff;
            --ca-purple: #a78bfa;
            --ca-yellow: #f6c96b;
            --ca-red: #ff7180;
            --ca-radius: 14px;
            --ca-radius-sm: 10px;
            --ca-shadow: 0 20px 70px rgba(0, 0, 0, .38);
            --ca-font: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ca-text);
            font-family: var(--ca-font);
            letter-spacing: 0;
            background:
                radial-gradient(circle at 12% 0%, rgba(53, 229, 155, .12), transparent 28%),
                radial-gradient(circle at 80% 10%, rgba(103, 167, 255, .12), transparent 30%),
                linear-gradient(180deg, #0b0f17 0%, #08101a 100%);
        }
        a { text-decoration: none; }
        button, input, textarea { font-family: inherit; }

        .tracker-page {
            min-height: 100vh;
            padding: 88px 18px 18px;
        }

        .tracker-nav {
            position: sticky;
            top: 14px;
            z-index: 100;
            display: grid;
            grid-template-columns: 240px minmax(360px, 1fr) 420px;
            gap: 16px;
            align-items: center;
            min-height: 68px;
            padding: 12px 14px;
            border: 1px solid var(--ca-line);
            border-radius: 18px;
            background: rgba(12, 17, 27, .74);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .32);
            backdrop-filter: blur(18px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.12);
            background: linear-gradient(135deg, rgba(53,229,155,.22), rgba(103,167,255,.18));
            color: var(--ca-text);
            font-weight: 800;
        }
        .brand-name {
            color: var(--ca-text);
            font-weight: 800;
            font-size: 1.02rem;
            line-height: 1.05;
            white-space: nowrap;
        }
        .brand-name span { color: var(--ca-green); }
        .brand-sub {
            display: block;
            margin-top: 4px;
            color: var(--ca-muted);
            font-size: .76rem;
            font-weight: 500;
        }

        .center-nav {
            display: flex;
            justify-content: center;
            gap: 6px;
            min-width: 0;
        }
        .center-nav a {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 13px;
            border-radius: 10px;
            color: var(--ca-muted);
            font-size: .86rem;
            font-weight: 650;
            transition: color .18s ease, background .18s ease, transform .18s ease;
        }
        .center-nav a:hover,
        .center-nav a.active {
            color: var(--ca-text);
            background: rgba(255,255,255,.07);
            transform: translateY(-1px);
        }

        .nav-actions {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) 42px auto;
            gap: 10px;
            align-items: center;
        }
        .search-box {
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 13px;
            border-radius: 12px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.055);
        }
        .search-icon {
            width: 14px;
            height: 14px;
            border: 2px solid var(--ca-dim);
            border-radius: 50%;
            position: relative;
            flex: 0 0 auto;
        }
        .search-icon::after {
            content: "";
            position: absolute;
            width: 7px;
            height: 2px;
            right: -6px;
            bottom: -4px;
            border-radius: 2px;
            background: var(--ca-dim);
            transform: rotate(45deg);
        }
        .search-box input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--ca-text);
            font-size: .9rem;
        }
        .search-box input::placeholder { color: var(--ca-dim); }

        .icon-button {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.055);
            color: var(--ca-muted);
            cursor: pointer;
            transition: transform .18s ease, border-color .18s ease, background .18s ease, color .18s ease;
        }
        .icon-button:hover {
            transform: translateY(-1px);
            border-color: rgba(103,167,255,.35);
            background: rgba(103,167,255,.09);
            color: var(--ca-text);
        }
        .bell {
            width: 14px;
            height: 16px;
            border: 2px solid currentColor;
            border-bottom: 0;
            border-radius: 9px 9px 4px 4px;
            position: relative;
        }
        .bell::before {
            content: "";
            position: absolute;
            top: -5px;
            left: 50%;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            transform: translateX(-50%);
        }
        .bell::after {
            content: "";
            position: absolute;
            left: 3px;
            right: 3px;
            bottom: -4px;
            height: 2px;
            border-radius: 2px;
            background: currentColor;
        }

        .profile-menu {
            position: relative;
        }
        .profile-trigger {
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 0 10px;
            border-radius: 12px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.055);
            color: var(--ca-text);
            cursor: pointer;
        }
        .avatar {
            width: 26px;
            height: 26px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: rgba(53,229,155,.14);
            color: var(--ca-green);
            font-weight: 800;
            font-size: .72rem;
        }
        .profile-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 188px;
            padding: 8px;
            border: 1px solid var(--ca-line);
            border-radius: 14px;
            background: rgba(17,23,35,.96);
            box-shadow: var(--ca-shadow);
            backdrop-filter: blur(18px);
            display: none;
        }
        .profile-menu.open .profile-dropdown { display: block; }
        .profile-dropdown a {
            display: block;
            padding: 10px;
            border-radius: 10px;
            color: var(--ca-muted);
            font-size: .86rem;
        }
        .profile-dropdown a:hover {
            color: var(--ca-text);
            background: rgba(255,255,255,.06);
        }
        .profile-dropdown button {
            width: 100%;
            display: block;
            padding: 10px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--ca-muted);
            font: inherit;
            font-size: .86rem;
            text-align: left;
            cursor: pointer;
        }
        .profile-dropdown button:hover {
            color: var(--ca-text);
            background: rgba(255,255,255,.06);
        }

        .dashboard-grid {
            max-width: 1540px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr) 320px;
            gap: 18px;
            align-items: start;
        }
        .tracker-toolbar {
            max-width: 1540px;
            margin: 0 auto 18px;
            display: grid;
            grid-template-columns: minmax(260px, 1fr) auto;
            gap: 16px;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--ca-line);
            border-radius: 18px;
            background: rgba(12, 17, 27, .74);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .22);
            backdrop-filter: blur(18px);
        }
        .toolbar-title {
            min-width: 0;
        }
        .toolbar-title h1 {
            margin: 0;
            color: var(--ca-text);
            font-size: 1.08rem;
            line-height: 1.15;
            letter-spacing: 0;
        }
        .toolbar-title p {
            margin: 4px 0 0;
            color: var(--ca-muted);
            font-size: .8rem;
        }
        .toolbar-actions {
            display: grid;
            grid-template-columns: minmax(220px, 360px) 42px auto;
            gap: 10px;
            align-items: center;
        }
        .glass-panel,
        .hero-card,
        .contest-card,
        .create-drawer {
            border: 1px solid var(--ca-line);
            background: var(--ca-glass);
            box-shadow: 0 18px 55px rgba(0, 0, 0, .22);
            backdrop-filter: blur(18px);
        }
        .glass-panel {
            border-radius: var(--ca-radius);
            overflow: hidden;
        }
        .left-sidebar,
        .right-sidebar {
            position: sticky;
            top: 104px;
        }
        .panel-section {
            padding: 18px;
            border-bottom: 1px solid var(--ca-line);
        }
        .panel-section:last-child { border-bottom: 0; }
        .section-label {
            margin: 0 0 14px;
            color: var(--ca-text);
            font-size: .77rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .filter-stack { display: grid; gap: 10px; }
        .filter-row {
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            color: var(--ca-muted);
            cursor: pointer;
            transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
        }
        .filter-row:hover,
        .filter-row.active {
            transform: translateY(-1px);
            color: var(--ca-text);
            border-color: var(--ca-line);
            background: rgba(255,255,255,.055);
        }
        .filter-left {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }
        .platform-badge,
        .platform-icon {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,.09);
            color: var(--ca-blue);
            background: rgba(103,167,255,.12);
            font-size: .68rem;
            font-weight: 900;
        }
        .platform-badge.cf, .platform-icon.cf { color: var(--ca-red); background: rgba(255,113,128,.12); }
        .platform-badge.cc, .platform-icon.cc { color: var(--ca-purple); background: rgba(167,139,250,.12); }
        .platform-badge.lc, .platform-icon.lc { color: var(--ca-yellow); background: rgba(246,201,107,.12); }
        .platform-badge.at, .platform-icon.at { color: #6ee7ff; background: rgba(110,231,255,.12); }
        .platform-badge.hr, .platform-icon.hr { color: var(--ca-green); background: rgba(53,229,155,.12); }
        .toggle {
            width: 36px;
            height: 20px;
            position: relative;
            flex: 0 0 auto;
        }
        .toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .toggle span {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: rgba(255,255,255,.13);
            transition: background .18s ease;
        }
        .toggle span::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 2px;
            left: 2px;
            border-radius: 50%;
            background: #dce4ef;
            transition: transform .18s ease, background .18s ease;
        }
        .toggle input:checked + span {
            background: linear-gradient(90deg, rgba(53,229,155,.9), rgba(103,167,255,.9));
        }
        .toggle input:checked + span::after {
            transform: translateX(16px);
            background: #07100d;
        }
        .status-filter {
            width: 100%;
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            background: transparent;
            color: var(--ca-muted);
            font: inherit;
            text-align: left;
            cursor: pointer;
            transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
        }
        .status-filter:hover,
        .status-filter.active {
            transform: translateY(-1px);
            color: var(--ca-text);
            border-color: var(--ca-line);
            background: rgba(255,255,255,.055);
        }
        .count-pill {
            color: var(--ca-dim);
            font-size: .78rem;
        }
        .saved-link {
            width: 100%;
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border-radius: 12px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.045);
            color: var(--ca-text);
            cursor: pointer;
        }
        .saved-link.active {
            border-color: rgba(246,201,107,.36);
            background: rgba(246,201,107,.1);
            color: var(--ca-yellow);
        }
        .saved-page-link {
            display: inline-flex;
            margin-top: 10px;
            color: var(--ca-muted);
            font-size: .78rem;
            font-weight: 750;
        }
        .saved-page-link:hover {
            color: var(--ca-green);
        }
        .focus-timer-card {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid rgba(53,229,155,.18);
            background: linear-gradient(135deg, rgba(53,229,155,.08), rgba(103,167,255,.045));
        }
        .focus-mode-pill {
            width: fit-content;
            justify-self: center;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid rgba(53,229,155,.28);
            background: rgba(53,229,155,.1);
            color: var(--ca-green);
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .focus-mode-pill.break {
            border-color: rgba(103,167,255,.3);
            background: rgba(103,167,255,.11);
            color: var(--ca-blue);
        }
        .focus-timer-display {
            color: var(--ca-text);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            text-align: center;
        }
        .focus-timer-sub {
            color: var(--ca-muted);
            font-size: .76rem;
            text-align: center;
        }
        .focus-timer-actions {
            display: grid;
            grid-template-columns: 1fr 64px;
            gap: 8px;
        }
        .focus-timer-mini {
            min-height: 38px;
            border-radius: 11px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.045);
            color: var(--ca-muted);
            cursor: pointer;
            font-weight: 800;
        }
        .focus-timer-mini:hover {
            color: var(--ca-text);
            border-color: rgba(103,167,255,.28);
        }
        .focus-session-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .focus-session-tile {
            padding: 9px;
            border-radius: 11px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.035);
            text-align: center;
        }
        .focus-session-tile strong {
            display: block;
            color: var(--ca-text);
            font-size: 1rem;
            line-height: 1;
        }
        .focus-session-tile span {
            display: block;
            margin-top: 5px;
            color: var(--ca-muted);
            font-size: .66rem;
        }
        .focus-suggestion {
            display: block;
            padding: 9px 10px;
            border-radius: 11px;
            border: 1px solid rgba(103,167,255,.18);
            background: rgba(103,167,255,.07);
            color: var(--ca-muted);
            font-size: .75rem;
            line-height: 1.35;
        }
        .focus-suggestion:hover {
            color: var(--ca-text);
            border-color: rgba(103,167,255,.34);
        }

        .main-content { min-width: 0; }
        .hero-card {
            position: relative;
            overflow: hidden;
            min-height: 230px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: center;
            padding: 28px 28px 70px;
            border-radius: 18px;
            background:
                linear-gradient(135deg, rgba(53,229,155,.14), rgba(103,167,255,.11) 54%, rgba(167,139,250,.12)),
                rgba(18,24,36,.78);
        }
        .hero-card.is-paused {
            border-color: rgba(53,229,155,.28);
        }
        .hero-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(255,255,255,.08), transparent 45%),
                radial-gradient(circle at 88% 20%, rgba(53,229,155,.2), transparent 32%);
            pointer-events: none;
        }
        .hero-content,
        .hero-timer {
            position: relative;
            z-index: 1;
        }
        .hero-content,
        .hero-timer {
            transition: opacity .38s ease, transform .38s ease;
        }
        .hero-card.hero-animating .hero-content {
            animation: heroSlideIn .48s ease both;
        }
        .hero-card.hero-animating .hero-timer {
            animation: heroTimerIn .48s ease both;
        }
        @keyframes heroSlideIn {
            from { opacity: 0; transform: translateX(34px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes heroTimerIn {
            from { opacity: 0; transform: translateX(-18px) scale(.98); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 13px;
            color: var(--ca-green);
            font-size: .78rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ca-green);
            box-shadow: 0 0 0 6px rgba(53,229,155,.12);
        }
        .hero-title {
            margin: 0;
            color: var(--ca-text);
            font-size: clamp(1.7rem, 4vw, 3.1rem);
            line-height: 1.06;
            letter-spacing: 0;
            max-width: 760px;
        }
        .hero-meta {
            margin-top: 15px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            color: var(--ca-muted);
            font-size: .9rem;
        }
        .hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
        }
        .hero-timer {
            width: 240px;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(8,12,20,.38);
            text-align: center;
        }
        .timer-label {
            color: var(--ca-muted);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .timer-value {
            margin-top: 10px;
            color: var(--ca-green);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 1.72rem;
            font-weight: 900;
        }
        .timer-date {
            margin-top: 9px;
            color: var(--ca-muted);
            font-size: .8rem;
        }
        .hero-carousel-controls {
            position: absolute;
            left: 28px;
            right: 28px;
            bottom: 18px;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            pointer-events: none;
        }
        .hero-carousel-left {
            display: flex;
            align-items: center;
            gap: 8px;
            pointer-events: auto;
        }
        .hero-nav-button {
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            border-radius: 11px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(8,12,20,.42);
            color: var(--ca-text);
            font-size: 1.1rem;
            cursor: pointer;
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }
        .hero-nav-button:hover {
            transform: translateY(-1px);
            border-color: rgba(103,167,255,.38);
            background: rgba(103,167,255,.12);
        }
        .hero-dots {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .hero-dot {
            width: 7px;
            height: 7px;
            border: 0;
            border-radius: 999px;
            background: rgba(255,255,255,.26);
            cursor: pointer;
            transition: width .22s ease, background .22s ease;
        }
        .hero-dot.active {
            width: 24px;
            background: var(--ca-green);
        }
        .hero-progress {
            width: min(220px, 32vw);
            height: 4px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            pointer-events: auto;
        }
        .hero-progress span {
            display: block;
            width: 0%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--ca-green), var(--ca-blue));
            transition: width .18s linear;
        }

        .button-primary,
        .button-ghost,
        .card-button {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 15px;
            border-radius: 12px;
            font-size: .86rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
        }
        .button-primary {
            border: 1px solid rgba(53,229,155,.38);
            background: linear-gradient(135deg, rgba(53,229,155,.96), rgba(103,167,255,.94));
            color: #06100d;
            box-shadow: 0 14px 34px rgba(53,229,155,.16);
        }
        .button-ghost,
        .card-button {
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.055);
            color: var(--ca-text);
        }
        .button-primary:hover,
        .button-ghost:hover,
        .card-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 34px rgba(0,0,0,.24);
            border-color: rgba(103,167,255,.36);
        }

        .list-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin: 24px 2px 12px;
        }
        .list-header h2 {
            margin: 0;
            color: var(--ca-text);
            font-size: 1.12rem;
            letter-spacing: 0;
        }
        .list-header p {
            margin: 5px 0 0;
            color: var(--ca-muted);
            font-size: .86rem;
        }
        .contest-list {
            display: grid;
            gap: 12px;
        }
        .contest-card {
            position: relative;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) 190px auto;
            gap: 14px;
            align-items: center;
            padding: 15px;
            border-radius: 16px;
            background: rgba(18,24,36,.68);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .contest-card:hover {
            transform: translateY(-2px);
            border-color: rgba(103,167,255,.34);
            background: rgba(24,32,48,.82);
            box-shadow: 0 18px 48px rgba(0,0,0,.32), 0 0 0 1px rgba(103,167,255,.08);
        }
        .contest-card.is-live {
            border-color: rgba(53,229,155,.34);
        }
        .contest-title-line {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }
        .contest-title-line h3 {
            margin: 0;
            font-size: .98rem;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .contest-title-line a { color: var(--ca-text); }
        .contest-title-line a:hover { color: var(--ca-green); }
        .status-chip {
            flex: 0 0 auto;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 850;
            border: 1px solid transparent;
        }
        .chip-active { color: var(--ca-green); background: rgba(53,229,155,.1); border-color: rgba(53,229,155,.22); }
        .chip-upcoming { color: var(--ca-blue); background: rgba(103,167,255,.1); border-color: rgba(103,167,255,.22); }
        .chip-ended { color: var(--ca-muted); background: rgba(255,255,255,.06); border-color: var(--ca-line); }
        .contest-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 7px;
            color: var(--ca-muted);
            font-size: .78rem;
        }
        .contest-time {
            text-align: right;
            color: var(--ca-muted);
            font-size: .78rem;
        }
        .contest-time strong {
            display: block;
            margin-bottom: 5px;
            color: var(--ca-text);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .9rem;
        }
        .contest-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
        .card-button {
            min-height: 36px;
            padding: 0 12px;
            border-radius: 11px;
            font-size: .78rem;
        }
        .card-button.primary {
            color: var(--ca-green);
            border-color: rgba(53,229,155,.32);
            background: rgba(53,229,155,.1);
        }
        .card-button.danger {
            color: var(--ca-red);
            border-color: rgba(255,113,128,.3);
        }
        .bookmark {
            width: 36px;
            padding: 0;
            color: var(--ca-muted);
        }
        .bookmark.saved {
            color: var(--ca-yellow);
            border-color: rgba(246,201,107,.34);
            background: rgba(246,201,107,.1);
        }

        .empty-state {
            min-height: 260px;
            display: grid;
            place-items: center;
            text-align: center;
            border: 1px dashed rgba(255,255,255,.14);
            border-radius: 16px;
            background: rgba(255,255,255,.035);
            color: var(--ca-muted);
        }
        .empty-state strong {
            display: block;
            margin-bottom: 6px;
            color: var(--ca-text);
        }

        .widget-card {
            padding: 14px;
            border-radius: 14px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.045);
        }
        .utility-panel {
            overflow: hidden;
        }
        .utility-card {
            display: grid;
            gap: 10px;
        }
        .utility-compact-title {
            margin: 0;
            color: var(--ca-text);
            font-size: .94rem;
            line-height: 1.25;
        }
        .utility-muted {
            color: var(--ca-muted);
            font-size: .78rem;
            line-height: 1.45;
        }
        .utility-timer {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 7px;
            padding: 7px 9px;
            border-radius: 10px;
            border: 1px solid rgba(53,229,155,.22);
            background: rgba(53,229,155,.08);
            color: var(--ca-green);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .82rem;
            font-weight: 900;
        }
        .insight-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px;
        }
        .insight-tile {
            padding: 11px;
            border-radius: 13px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.04);
        }
        .insight-tile strong {
            display: block;
            color: var(--ca-text);
            font-size: 1.05rem;
            line-height: 1;
        }
        .insight-tile span {
            display: block;
            margin-top: 6px;
            color: var(--ca-muted);
            font-size: .72rem;
            line-height: 1.25;
        }
        .highlight-list,
        .recommendation-list {
            display: grid;
            gap: 8px;
        }
        .highlight-item {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 9px;
            align-items: center;
            padding: 9px;
            border-radius: 13px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.035);
            color: inherit;
            cursor: pointer;
            font: inherit;
            text-align: left;
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }
        .highlight-item:hover {
            transform: translateY(-1px);
            border-color: rgba(103,167,255,.28);
            background: rgba(103,167,255,.07);
        }
        .highlight-item strong {
            display: block;
            min-width: 0;
            color: var(--ca-text);
            font-size: .8rem;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .highlight-copy span {
            display: block;
            margin-top: 3px;
            color: var(--ca-muted);
            font-size: .72rem;
        }
        .recommendation-item {
            padding: 10px;
            border-radius: 13px;
            border: 1px solid rgba(53,229,155,.14);
            background: linear-gradient(135deg, rgba(53,229,155,.07), rgba(103,167,255,.045));
        }
        .recommendation-item strong {
            display: block;
            color: var(--ca-text);
            font-size: .82rem;
            line-height: 1.25;
        }
        .recommendation-item span {
            display: block;
            margin-top: 5px;
            color: var(--ca-muted);
            font-size: .74rem;
            line-height: 1.35;
        }
        .calendar-board {
            margin-top: 22px;
            border-radius: 18px;
            border: 1px solid var(--ca-line);
            background: rgba(18,24,36,.68);
            box-shadow: 0 18px 55px rgba(0, 0, 0, .22);
            backdrop-filter: blur(18px);
            overflow: hidden;
        }
        .calendar-board-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px;
            border-bottom: 1px solid var(--ca-line);
        }
        .calendar-board-title h2 {
            margin: 0;
            color: var(--ca-text);
            font-size: 1.08rem;
            letter-spacing: 0;
        }
        .calendar-board-title p {
            margin: 5px 0 0;
            color: var(--ca-muted);
            font-size: .84rem;
        }
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 999px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.045);
            color: var(--ca-muted);
            font-size: .72rem;
            font-weight: 700;
        }
        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
        }
        .full-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }
        .full-calendar-head {
            min-height: 38px;
            display: grid;
            place-items: center;
            border-right: 1px solid var(--ca-line);
            border-bottom: 1px solid var(--ca-line);
            color: var(--ca-dim);
            font-size: .74rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .full-calendar-head:nth-child(7) {
            border-right: 0;
        }
        .calendar-cell {
            min-height: 132px;
            padding: 10px;
            border-right: 1px solid var(--ca-line);
            border-bottom: 1px solid var(--ca-line);
            background: rgba(255,255,255,.018);
            transition: background .18s ease, box-shadow .18s ease;
        }
        .calendar-cell:nth-child(7n) {
            border-right: 0;
        }
        .calendar-cell:hover {
            background: rgba(255,255,255,.045);
            box-shadow: inset 0 0 0 1px rgba(103,167,255,.18);
        }
        .calendar-cell.is-muted {
            opacity: .38;
        }
        .calendar-cell.is-today {
            background: rgba(53,229,155,.055);
            box-shadow: inset 0 0 0 1px rgba(53,229,155,.25);
        }
        .calendar-date-row,
        .calendar-cell-date {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            color: var(--ca-muted);
            font-size: .78rem;
            font-weight: 800;
        }
        .calendar-cell-date strong {
            color: var(--ca-text);
        }
        .calendar-date-row .today-mark {
            padding: 2px 6px;
            border-radius: 999px;
            background: rgba(53,229,155,.12);
            color: var(--ca-green);
            font-size: .64rem;
        }
        .calendar-events,
        .calendar-cell-events {
            display: grid;
            gap: 5px;
        }
        .contest-pill {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 25px;
            padding: 4px 7px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            font-size: .7rem;
            font-weight: 800;
            text-align: left;
            cursor: pointer;
            background: var(--pill-color, var(--ca-blue));
            box-shadow: 0 8px 20px rgba(0,0,0,.22);
            transition: transform .16s ease, filter .16s ease;
        }
        .contest-pill.more-pill {
            justify-content: center;
            background: rgba(255,255,255,.08);
            color: var(--ca-muted);
            border: 1px dashed var(--ca-line);
        }
        .contest-pill:hover {
            transform: translateY(-1px);
            filter: brightness(1.08);
        }
        .contest-pill-time {
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .62rem;
            opacity: .86;
        }
        .contest-pill-title {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .create-drawer {
            margin-top: 14px;
            padding: 18px;
            border-radius: 16px;
        }
        .create-drawer h3 {
            margin: 0 0 16px;
            color: var(--ca-text);
        }
        .create-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .form-group.full { grid-column: 1 / -1; }
        .date-time-pair {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 132px;
            gap: 10px;
        }
        .date-time-pair .form-input {
            color-scheme: dark;
        }
        .field-hint {
            margin-top: 6px;
            color: var(--ca-muted);
            font-size: .74rem;
            line-height: 1.35;
        }

        .delete-overlay {
            position: fixed;
            inset: 0;
            z-index: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(0,0,0,.72);
            backdrop-filter: blur(10px);
        }
        .delete-card {
            width: min(390px, 100%);
            padding: 28px;
            text-align: center;
            border-radius: 18px;
            border: 1px solid var(--ca-line);
            background: rgba(18,24,36,.96);
            box-shadow: var(--ca-shadow);
        }
        .delete-card h3 {
            margin: 0 0 10px;
            color: var(--ca-text);
        }
        .delete-card p {
            margin: 0 0 22px;
            color: var(--ca-muted);
            font-size: .88rem;
        }
        .delete-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .contest-modal {
            position: fixed;
            inset: 0;
            z-index: 690;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(0,0,0,.72);
            backdrop-filter: blur(10px);
        }
        .contest-modal-card {
            width: min(520px, 100%);
            border-radius: 18px;
            border: 1px solid var(--ca-line);
            background: rgba(18,24,36,.98);
            box-shadow: var(--ca-shadow);
            overflow: hidden;
        }
        .contest-modal-head {
            padding: 20px;
            border-bottom: 1px solid var(--ca-line);
            background: linear-gradient(135deg, rgba(103,167,255,.1), rgba(53,229,155,.07));
        }
        .contest-modal-head h3 {
            margin: 10px 0 0;
            color: var(--ca-text);
            line-height: 1.2;
            letter-spacing: 0;
        }
        .modal-platform {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            color: #fff;
            font-size: .76rem;
            font-weight: 900;
        }
        .contest-modal-body {
            padding: 20px;
        }
        .modal-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 18px;
        }
        .modal-meta-card {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.045);
        }
        .modal-meta-card span {
            display: block;
            color: var(--ca-dim);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .modal-meta-card strong {
            display: block;
            margin-top: 5px;
            color: var(--ca-text);
            font-size: .9rem;
        }
        .reminder-box {
            padding: 14px;
            border-radius: 14px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.035);
        }
        .reminder-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .reminder-option {
            min-height: 34px;
            padding: 0 11px;
            border-radius: 10px;
            border: 1px solid var(--ca-line);
            background: rgba(255,255,255,.045);
            color: var(--ca-muted);
            font-size: .8rem;
            font-weight: 800;
            cursor: pointer;
        }
        .reminder-option.active {
            border-color: rgba(53,229,155,.34);
            background: rgba(53,229,155,.11);
            color: var(--ca-green);
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 20px;
            border-top: 1px solid var(--ca-line);
            background: rgba(255,255,255,.025);
        }

        @media (max-width: 1240px) {
            .tracker-nav {
                grid-template-columns: 220px 1fr;
            }
            .center-nav {
                order: 3;
                grid-column: 1 / -1;
                justify-content: flex-start;
                overflow-x: auto;
                padding-top: 4px;
            }
            .nav-actions {
                grid-template-columns: minmax(180px, 1fr) 42px auto;
            }
            .dashboard-grid {
                grid-template-columns: 240px minmax(0, 1fr);
            }
            .right-sidebar {
                position: static;
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 900px) {
            .tracker-page { padding: 12px; }
            .tracker-nav {
                position: static;
                grid-template-columns: 1fr;
            }
            .nav-actions {
                grid-template-columns: 1fr 42px auto;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .tracker-toolbar {
                grid-template-columns: 1fr;
                margin-top: 72px;
            }
            .toolbar-actions {
                grid-template-columns: 1fr 42px auto;
            }
            .left-sidebar,
            .right-sidebar {
                position: static;
            }
            .hero-card {
                grid-template-columns: 1fr;
            }
            .hero-carousel-controls {
                left: 18px;
                right: 18px;
                bottom: 16px;
            }
            .hero-progress {
                width: min(180px, 40vw);
            }
            .calendar-board-head {
                align-items: flex-start;
                flex-direction: column;
            }
            .full-calendar-grid {
                grid-template-columns: 1fr;
            }
            .full-calendar-head {
                display: none;
            }
            .calendar-cell {
                min-height: 96px;
                border-right: 0;
            }
            .calendar-cell.is-muted {
                display: none;
            }
            .modal-meta-grid {
                grid-template-columns: 1fr;
            }
            .hero-timer {
                width: 100%;
                text-align: left;
            }
            .contest-card {
                grid-template-columns: 44px minmax(0, 1fr);
            }
            .contest-time,
            .contest-actions {
                grid-column: 1 / -1;
                text-align: left;
                justify-content: flex-start;
                padding-left: 58px;
            }
            .right-sidebar {
                display: grid;
                grid-template-columns: 1fr;
            }
            .create-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full {
                grid-column: auto;
            }
        }
        @media (max-width: 560px) {
            .tracker-page {
                padding-left: 12px;
                padding-right: 12px;
            }
            .nav-actions {
                grid-template-columns: 1fr 42px 42px;
            }
            .toolbar-actions {
                grid-template-columns: 1fr 42px 42px;
            }
            .profile-trigger .profile-name {
                display: none;
            }
            .contest-card {
                grid-template-columns: 1fr;
            }
            .contest-time,
            .contest-actions {
                padding-left: 0;
            }
            .platform-icon {
                display: none;
            }
            .hero-title {
                font-size: 1.7rem;
            }
            .hero-carousel-controls {
                position: relative;
                left: auto;
                right: auto;
                bottom: auto;
                margin-top: 18px;
                align-items: flex-start;
                flex-direction: column;
            }
            .hero-progress {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>
<div class="tracker-page">
    <div class="tracker-toolbar">
        <div class="toolbar-title">
            <h1>Contest Tracker</h1>
            <p>Track live, upcoming, and past competitive programming contests.</p>
        </div>
        <div class="toolbar-actions">
            <label class="search-box">
                <span class="search-icon" aria-hidden="true"></span>
                <input id="contest-search" type="search" placeholder="Search contests">
            </label>
            <button class="icon-button" type="button" title="Notifications"><span class="bell"></span></button>
            <div class="profile-menu" id="profile-menu">
                <button class="profile-trigger" type="button" onclick="toggleProfileMenu()">
                    <span class="avatar"><?= htmlspecialchars($initial) ?></span>
                    <span class="profile-name"><?= htmlspecialchars($username) ?></span>
                </button>
                <div class="profile-dropdown">
                    <a href="/code-arena/profile.php">Profile</a>
                    <a href="/code-arena/dashboard.php">Dashboard</a>
                    <?php if (isLoggedIn()): ?>
                    <button type="button" onclick="logoutFromTracker()">Logout</button>
                    <?php else: ?>
                    <a href="/code-arena/login.php">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <aside class="glass-panel left-sidebar">
            <section class="panel-section">
                <h2 class="section-label">Platforms</h2>
                <div class="filter-stack">
                    <label class="filter-row active">
                        <span class="filter-left"><span class="platform-badge cf">CF</span>Codeforces</span>
                        <span class="toggle"><input class="platform-filter" type="checkbox" value="codeforces" checked><span></span></span>
                    </label>
                    <label class="filter-row active">
                        <span class="filter-left"><span class="platform-badge cc">CC</span>CodeChef</span>
                        <span class="toggle"><input class="platform-filter" type="checkbox" value="codechef" checked><span></span></span>
                    </label>
                    <label class="filter-row active">
                        <span class="filter-left"><span class="platform-badge lc">LC</span>LeetCode</span>
                        <span class="toggle"><input class="platform-filter" type="checkbox" value="leetcode" checked><span></span></span>
                    </label>
                    <label class="filter-row active">
                        <span class="filter-left"><span class="platform-badge at">AT</span>AtCoder</span>
                        <span class="toggle"><input class="platform-filter" type="checkbox" value="atcoder" checked><span></span></span>
                    </label>
                    <label class="filter-row active">
                        <span class="filter-left"><span class="platform-badge hr">HR</span>HackerRank</span>
                        <span class="toggle"><input class="platform-filter" type="checkbox" value="hackerrank" checked><span></span></span>
                    </label>
                </div>
            </section>

            <section class="panel-section">
                <h2 class="section-label">Status</h2>
                <div class="filter-stack">
                    <button class="status-filter active" data-status="">All Contests <span class="count-pill" id="count-all">0</span></button>
                    <button class="status-filter" data-status="active">Live <span class="count-pill" id="count-active">0</span></button>
                    <button class="status-filter" data-status="upcoming">Upcoming <span class="count-pill" id="count-upcoming">0</span></button>
                    <button class="status-filter" data-status="ended">Past <span class="count-pill" id="count-ended">0</span></button>
                </div>
            </section>

            <section class="panel-section">
                <h2 class="section-label">Focus Timer</h2>
                <div class="focus-timer-card">
                    <div>
                        <div class="focus-mode-pill" id="focus-mode-pill">Work Mode</div>
                        <div class="focus-timer-display" id="focus-timer-display">25:00</div>
                        <div class="focus-timer-sub" id="focus-timer-status">Study mode ready</div>
                    </div>
                    <div class="focus-session-grid">
                        <div class="focus-session-tile"><strong id="focus-work-count">0</strong><span>Work sessions</span></div>
                        <div class="focus-session-tile"><strong id="focus-total-count">0</strong><span>Page total</span></div>
                    </div>
                    <a class="focus-suggestion" id="focus-practice-link" href="/code-arena/problems.php?tag=dynamic-programming">Start timer to unlock a focused practice suggestion.</a>
                    <div class="focus-timer-actions">
                        <button class="button-primary" type="button" id="focus-timer-toggle" onclick="toggleFocusTimer()">Start</button>
                        <button class="focus-timer-mini" type="button" title="Reset focus timer" onclick="resetFocusTimer()">Reset</button>
                    </div>
                </div>
            </section>

            <section class="panel-section">
                <h2 class="section-label">Saved</h2>
                <button class="saved-link" id="saved-toggle" type="button">
                    Saved Contests <span class="count-pill" id="count-saved">0</span>
                </button>
                <a class="saved-page-link" href="/code-arena/saved-contests.php">Open saved page</a>
            </section>

            <?php if (isInstructor()): ?>
            <section class="panel-section">
                <button class="button-primary" style="width:100%" type="button" onclick="showCreateForm()">New Contest</button>
            </section>
            <?php endif; ?>
        </aside>

        <main class="main-content">
            <section class="hero-card" id="hero-card">
                <div class="hero-content">
                    <div class="eyebrow"><span class="pulse-dot"></span>Next Contest</div>
                    <h1 class="hero-title" id="hero-title">Loading contest schedule</h1>
                    <div class="hero-meta" id="hero-meta"></div>
                    <div class="hero-actions" id="hero-actions"></div>
                </div>
                <div class="hero-timer">
                    <div class="timer-label" id="hero-timer-label">Countdown</div>
                    <div class="timer-value" id="hero-countdown">--:--:--</div>
                    <div class="timer-date" id="hero-date">Please wait</div>
                </div>
                <div class="hero-carousel-controls" id="hero-carousel-controls">
                    <div class="hero-carousel-left">
                        <button class="hero-nav-button" type="button" title="Previous contest" onclick="moveHeroSlide(-1)">&lsaquo;</button>
                        <button class="hero-nav-button" type="button" title="Next contest" onclick="moveHeroSlide(1)">&rsaquo;</button>
                        <div class="hero-dots" id="hero-dots"></div>
                    </div>
                    <div class="hero-progress" title="Auto slide progress"><span id="hero-progress"></span></div>
                </div>
            </section>

            <section class="calendar-board">
                <div class="calendar-board-head">
                    <div class="calendar-board-title">
                        <h2 id="full-calendar-title">Contest Calendar</h2>
                        <p>Demo and live contests are grouped by start date.</p>
                    </div>
                    <div class="calendar-legend" id="calendar-legend"></div>
                </div>
                <div class="full-calendar-grid" id="full-calendar-grid"></div>
            </section>

            <div class="list-header">
                <div>
                    <h2 id="list-title">Contest List</h2>
                    <p id="list-subtitle">Loading contests</p>
                </div>
                <button class="button-ghost" type="button" onclick="loadContests(activeStatus)">Refresh</button>
            </div>

            <div class="contest-list" id="contests-grid">
                <div class="empty-state"><div><strong>Loading contests</strong><span>Please wait</span></div></div>
            </div>

            <?php if (isInstructor()): ?>
            <div id="create-form" style="display:none" class="create-drawer">
                <h3>Create Contest</h3>
                <div class="create-grid">
                    <div class="form-group full">
                        <label class="form-label">Title *</label>
                        <input type="text" id="ct-title" class="form-input" placeholder="Weekly Contest #1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contest Date *</label>
                        <div class="date-time-pair">
                            <input type="date" id="ct-start-date" class="form-input" placeholder="2026-06-20" aria-label="Contest start date" title="Contest Date: YYYY-MM-DD" pattern="\d{4}-\d{2}-\d{2}" inputmode="numeric">
                            <input type="time" id="ct-start-time" class="form-input" placeholder="18:30" aria-label="Contest start time" title="Contest Time: HH:MM" pattern="[0-2][0-9]:[0-5][0-9]">
                        </div>
                        <div class="field-hint">Start date and time, e.g. 2026-06-20 at 18:30.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date & Time *</label>
                        <div class="date-time-pair">
                            <input type="date" id="ct-end-date" class="form-input" placeholder="2026-06-20" aria-label="Contest end date" title="Contest Date: YYYY-MM-DD" pattern="\d{4}-\d{2}-\d{2}" inputmode="numeric">
                            <input type="time" id="ct-end-time" class="form-input" placeholder="20:30" aria-label="Contest end time" title="Contest Time: HH:MM" pattern="[0-2][0-9]:[0-5][0-9]">
                        </div>
                        <div class="field-hint">End date and time. Manual typing still works.</div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Problem IDs</label>
                        <input type="text" id="ct-problems" class="form-input" placeholder="1, 2, 3, 4">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Description</label>
                        <textarea id="ct-desc" class="form-input" rows="3" placeholder="Contest description"></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:center;margin-top:12px;flex-wrap:wrap">
                    <button class="button-primary" onclick="createContest()">Create</button>
                    <button class="button-ghost" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
                    <span id="ct-msg" style="font-size:.88rem"></span>
                </div>
            </div>
            <?php endif; ?>
        </main>

        <aside class="glass-panel right-sidebar utility-panel">
            <section class="panel-section">
                <h2 class="section-label">Next Contest</h2>
                <div class="widget-card utility-card" id="utility-next-contest"></div>
            </section>
            <section class="panel-section">
                <h2 class="section-label">Smart Insights</h2>
                <div class="insight-grid" id="utility-insights"></div>
            </section>
            <section class="panel-section">
                <h2 class="section-label">Upcoming Highlights</h2>
                <div class="highlight-list" id="utility-highlights"></div>
            </section>
            <section class="panel-section">
                <h2 class="section-label">Recommendations</h2>
                <div class="recommendation-list" id="utility-recommendations"></div>
            </section>
        </aside>
    </div>
</div>

<div class="contest-modal" id="contest-modal" style="display:none" onclick="closeContestModal(event)">
    <div class="contest-modal-card">
        <div class="contest-modal-head">
            <span class="modal-platform" id="modal-platform">Platform</span>
            <h3 id="modal-title">Contest details</h3>
        </div>
        <div class="contest-modal-body">
            <div class="modal-meta-grid">
                <div class="modal-meta-card">
                    <span>Start Time</span>
                    <strong id="modal-start">-</strong>
                </div>
                <div class="modal-meta-card">
                    <span>Countdown</span>
                    <strong id="modal-countdown">-</strong>
                </div>
            </div>
            <div class="reminder-box">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <div>
                        <strong style="display:block;color:var(--ca-text)">Reminder</strong>
                        <span id="modal-reminder-status" style="display:block;margin-top:4px;color:var(--ca-muted);font-size:.8rem">No reminder set</span>
                    </div>
                    <button class="button-primary" type="button" onclick="saveReminder()">Save Reminder</button>
                </div>
                <div class="reminder-options">
                    <button class="reminder-option active" type="button" data-minutes="10" onclick="selectReminder(10,this)">10 min</button>
                    <button class="reminder-option" type="button" data-minutes="60" onclick="selectReminder(60,this)">1 hr</button>
                    <button class="reminder-option" type="button" data-minutes="1440" onclick="selectReminder(1440,this)">1 day</button>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="button-ghost" type="button" onclick="closeContestModal()">Close</button>
            <a class="button-primary" id="modal-details-link" href="#">View Details</a>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<div class="delete-overlay" id="delete-overlay" style="display:none" onclick="closeDeleteModal(event)">
    <div class="delete-card">
        <h3>Delete Contest?</h3>
        <p>This cannot be undone. All problems, registrations, and results for this contest will be removed.</p>
        <div class="delete-actions">
            <button class="button-ghost" onclick="closeDeleteModal()">Cancel</button>
            <button class="card-button danger" id="delete-confirm-btn" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
const CAN_MANAGE = <?= isInstructor() ? 'true' : 'false' ?>;
const SAVE_KEY = 'code_arena_saved_contests';
const REMINDER_KEY = 'code_arena_contest_reminders';
const demoContests = [
  {
    id: 1,
    title: "Codeforces Round 1050",
    platform: "Codeforces",
    start_time: "2026-06-16 18:00:00"
  },
  {
    id: 2,
    title: "LeetCode Weekly Contest 420",
    platform: "LeetCode",
    start_time: "2026-06-16 10:00:00"
  },
  {
    id: 3,
    title: "CodeChef Starter 120",
    platform: "CodeChef",
    start_time: "2026-06-18 20:00:00"
  },
  {
    id: 4,
    title: "AtCoder Beginner Contest 350",
    platform: "AtCoder",
    start_time: "2026-06-20 12:00:00"
  },
  {
    id: 5,
    title: "HackerRank Weekly Challenge",
    platform: "HackerRank",
    start_time: "2026-06-20 15:00:00"
  }
];
const platformColors = {
    Codeforces: '#3B82F6',
    CodeChef: '#F97316',
    LeetCode: '#EAB308',
    AtCoder: '#A855F7',
    HackerRank: '#22C55E',
    'Code Arena': '#3B82F6'
};

let allContests = [];
let activeStatus = '';
let savedOnly = false;
let deleteTargetId = null;
let deleteTargetCard = null;
let selectedContestId = null;
let selectedReminderMinutes = 10;
let heroIndex = 0;
let heroPaused = false;
let heroSlideStartedAt = Date.now();
let lastHeroContestId = null;
const HERO_SLIDE_MS = 4500;
const FOCUS_DEFAULT_SECONDS = 25 * 60;
const BREAK_DEFAULT_SECONDS = 5 * 60;
let focusMode = 'work';
let focusRemainingSeconds = FOCUS_DEFAULT_SECONDS;
let focusTimerId = null;
let focusRunning = false;
let focusWorkSessions = 0;
let focusTotalSessions = 0;

function toggleProfileMenu() {
    document.getElementById('profile-menu').classList.toggle('open');
}

async function logoutFromTracker() {
    const { ok } = await api('/code-arena/api/auth/logout.php', { method: 'DELETE' });
    if (ok) window.location.href = '/code-arena/login.php';
}

document.addEventListener('click', event => {
    const menu = document.getElementById('profile-menu');
    if (menu && !menu.contains(event.target)) menu.classList.remove('open');
});

function savedIds() {
    try {
        return JSON.parse(localStorage.getItem(SAVE_KEY) || '[]').map(String);
    } catch (_) {
        return [];
    }
}

function setSavedIds(ids) {
    localStorage.setItem(SAVE_KEY, JSON.stringify([...new Set(ids.map(String))]));
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[ch]));
}

function inferPlatform(contest) {
    if (contest.platform) {
        const label = cleanPlatformName(contest.platform);
        const keyMap = {
            Codeforces: 'codeforces',
            CodeChef: 'codechef',
            LeetCode: 'leetcode',
            AtCoder: 'atcoder',
            HackerRank: 'hackerrank',
            'Code Arena': 'codeforces'
        };
        const shortMap = { Codeforces:'CF', CodeChef:'CC', LeetCode:'LC', AtCoder:'AT', HackerRank:'HR', 'Code Arena':'CA' };
        const clsMap = { Codeforces:'cf', CodeChef:'cc', LeetCode:'lc', AtCoder:'at', HackerRank:'hr', 'Code Arena':'' };
        return { key: keyMap[label] || 'codeforces', label, short: shortMap[label] || 'CA', cls: clsMap[label] || '' };
    }
    const title = String(contest.title || '').toLowerCase();
    if (title.includes('codeforces') || title.includes(' cf ')) return { key: 'codeforces', label: 'Codeforces', short: 'CF', cls: 'cf' };
    if (title.includes('codechef') || title.includes('cook-off') || title.includes('lunchtime')) return { key: 'codechef', label: 'CodeChef', short: 'CC', cls: 'cc' };
    if (title.includes('leetcode') || title.includes('weekly contest') || title.includes('biweekly')) return { key: 'leetcode', label: 'LeetCode', short: 'LC', cls: 'lc' };
    if (title.includes('atcoder') || title.includes('abc') || title.includes('arc')) return { key: 'atcoder', label: 'AtCoder', short: 'AT', cls: 'at' };
    if (title.includes('hackerrank')) return { key: 'hackerrank', label: 'HackerRank', short: 'HR', cls: 'hr' };
    return { key: 'codeforces', label: 'Code Arena', short: 'CA', cls: '' };
}

function cleanPlatformName(platform) {
    const value = String(platform || '').toLowerCase();
    if (value.includes('codechef')) return 'CodeChef';
    if (value.includes('leetcode')) return 'LeetCode';
    if (value.includes('atcoder')) return 'AtCoder';
    if (value.includes('hackerrank')) return 'HackerRank';
    if (value.includes('codeforces')) return 'Codeforces';
    return platform === 'Code Arena' ? 'Code Arena' : 'Codeforces';
}

function normalizeContest(contest, source = 'api') {
    const platform = inferPlatform(contest);
    const id = source === 'demo' ? `demo-${contest.id}` : String(contest.id);
    const start = contest.start_time || contest.startTime || new Date().toISOString();
    const end = contest.end_time || contest.endTime || addHours(start, 2);
    return {
        ...contest,
        id,
        original_id: contest.id,
        source,
        platform: platform.label,
        start_time: start,
        end_time: end,
        status: contest.status || statusFromStartEnd(start, end),
        participant_count: contest.participant_count || contest.participants || 0,
    };
}

function addHours(dateValue, hours) {
    const date = new Date(dateValue);
    date.setHours(date.getHours() + hours);
    return toMysqlDateTime(date);
}

function toMysqlDateTime(date) {
    return date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0') + ' ' +
        String(date.getHours()).padStart(2, '0') + ':' +
        String(date.getMinutes()).padStart(2, '0') + ':' +
        String(date.getSeconds()).padStart(2, '0');
}

function localDateKey(date) {
    return date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0');
}

function statusFromStartEnd(startValue, endValue) {
    const now = new Date();
    const start = new Date(startValue);
    const end = new Date(endValue);
    if (now < start) return 'upcoming';
    if (now <= end) return 'active';
    return 'ended';
}

async function loadContests(status = '') {
    activeStatus = status;
    document.querySelectorAll('.status-filter').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.status === status);
    });

    const params = status ? `?status=${encodeURIComponent(status)}` : '';
    const apiData = await fetchContestData(params);

    if (!apiData.ok) {
        allContests = [];
        renderEverything();
        toast('Contest feed could not be loaded', 'error');
        return;
    }

    const normalizedApi = (apiData.data || []).map(contest => normalizeContest(contest, 'api'));
    allContests = normalizedApi.sort((a, b) => new Date(a.start_time) - new Date(b.start_time));
    heroIndex = 0;
    heroSlideStartedAt = Date.now();
    renderEverything();
}

async function fetchContestData(params) {
    const endpoint = `/code-arena/api/contests/feed.php${params}`;
    try {
        const { ok, data } = await api(endpoint, { cache: 'no-store' });
        if (ok && data.success && Array.isArray(data.data)) {
            return { ok: true, data: data.data };
        }
    } catch (_) {}
    return { ok: false, data: [] };
}

function renderEverything() {
    renderCounts();
    renderHero();
    renderFullCalendar();
    renderContestList();
    renderUtilityPanel();
    if (selectedContestId && document.getElementById('contest-modal').style.display === 'flex') {
        const contest = contestById(selectedContestId);
        if (contest) {
            const target = contest.status === 'active' ? new Date(contest.end_time) : new Date(contest.start_time);
            document.getElementById('modal-countdown').textContent = contest.status === 'ended' ? 'Completed' : preciseCountdown(target - new Date());
        }
    }
}

function filteredContests() {
    const term = document.getElementById('contest-search').value.trim().toLowerCase();
    const platforms = [...document.querySelectorAll('.platform-filter:checked')].map(input => input.value);
    const saved = savedIds();

    return allContests.filter(contest => {
        const platform = inferPlatform(contest);
        if (!platforms.includes(platform.key)) return false;
        if (savedOnly && !saved.includes(String(contest.id))) return false;
        if (term) {
            const haystack = `${contest.title || ''} ${contest.author || ''} ${contest.status || ''} ${platform.label}`.toLowerCase();
            if (!haystack.includes(term)) return false;
        }
        return true;
    });
}

function renderCounts() {
    const counts = { all: allContests.length, active: 0, upcoming: 0, ended: 0 };
    allContests.forEach(contest => {
        if (counts[contest.status] !== undefined) counts[contest.status]++;
    });
    document.getElementById('count-all').textContent = counts.all;
    document.getElementById('count-active').textContent = counts.active;
    document.getElementById('count-upcoming').textContent = counts.upcoming;
    document.getElementById('count-ended').textContent = counts.ended;
    document.getElementById('count-saved').textContent = savedIds().length;
}

function heroContests() {
    return [...allContests]
        .filter(contest => contest.status !== 'ended')
        .sort((a, b) => {
            const aTime = a.status === 'active' ? new Date(a.end_time) : new Date(a.start_time);
            const bTime = b.status === 'active' ? new Date(b.end_time) : new Date(b.start_time);
            return aTime - bTime;
        });
}

function nextContest() {
    return heroContests()[0] || null;
}

function currentHeroContest() {
    const contests = heroContests();
    if (!contests.length) return null;
    if (heroIndex >= contests.length) heroIndex = 0;
    if (heroIndex < 0) heroIndex = contests.length - 1;
    return contests[heroIndex] || contests[0];
}

function renderHero() {
    const contests = heroContests();
    const contest = currentHeroContest();
    const card = document.getElementById('hero-card');
    const title = document.getElementById('hero-title');
    const meta = document.getElementById('hero-meta');
    const actions = document.getElementById('hero-actions');
    const countdown = document.getElementById('hero-countdown');
    const date = document.getElementById('hero-date');
    const label = document.getElementById('hero-timer-label');
    const controls = document.getElementById('hero-carousel-controls');
    const dots = document.getElementById('hero-dots');
    const progress = document.getElementById('hero-progress');

    if (!contest) {
        title.textContent = 'No upcoming contests scheduled';
        meta.innerHTML = '<span>Keep practicing while the next round is prepared.</span>';
        actions.innerHTML = '<a class="button-primary" href="/code-arena/problems.php">Practice Problems</a>';
        countdown.textContent = '--:--:--';
        date.textContent = 'No active schedule';
        label.textContent = 'Countdown';
        if (controls) controls.style.display = 'none';
        if (progress) progress.style.width = '0%';
        lastHeroContestId = null;
        return;
    }

    if (controls) controls.style.display = contests.length > 1 ? 'flex' : 'none';
    if (card) card.classList.toggle('is-paused', heroPaused);
    if (String(contest.id) !== String(lastHeroContestId)) {
        lastHeroContestId = contest.id;
        if (card) {
            card.classList.remove('hero-animating');
            void card.offsetWidth;
            card.classList.add('hero-animating');
            window.setTimeout(() => card.classList.remove('hero-animating'), 520);
        }
    }

    const platform = inferPlatform(contest);
    const target = contest.status === 'active' ? new Date(contest.end_time) : new Date(contest.start_time);
    const href = contestHref(contest);
    const demoClick = contest.source === 'demo' ? ` onclick="event.preventDefault();openContestModal('${escapeJs(contest.id)}')"` : '';
    title.textContent = contest.title || 'Untitled Contest';
    meta.innerHTML = `
        <span><span class="platform-badge ${platform.cls}" style="display:inline-grid;width:24px;height:24px;border-radius:8px">${platform.short}</span>${escapeHtml(platform.label)}</span>
        <span>${escapeHtml(contest.author || 'Code Arena')}</span>
        <span>${formatDateTime(new Date(contest.start_time))}</span>
        <span>${Number(contest.participant_count || 0)} participants</span>
    `;
    actions.innerHTML = `
        <a class="button-primary" href="${href}"${demoClick}>${contest.status === 'active' ? 'Enter Contest' : 'Register'}</a>
        <button class="button-ghost" type="button" onclick="openContestModal('${escapeJs(contest.id)}')">View Details</button>
    `;
    countdown.textContent = preciseCountdown(target - new Date());
    date.textContent = formatDateTime(new Date(contest.start_time));
    label.textContent = contest.status === 'active' ? 'Ends In' : 'Starts In';

    if (dots) {
        dots.innerHTML = contests.map((item, index) => `
            <button class="hero-dot ${index === heroIndex ? 'active' : ''}" type="button" title="${escapeHtml(item.title)}" onclick="goToHeroSlide(${index})"></button>
        `).join('');
    }
    if (progress) {
        const pct = heroPaused ? progress.style.width : `${Math.min(100, ((Date.now() - heroSlideStartedAt) / HERO_SLIDE_MS) * 100)}%`;
        progress.style.width = pct;
    }
}

function moveHeroSlide(direction = 1) {
    const contests = heroContests();
    if (!contests.length) return;
    heroIndex = (heroIndex + direction + contests.length) % contests.length;
    heroSlideStartedAt = Date.now();
    renderHero();
}

function goToHeroSlide(index) {
    const contests = heroContests();
    if (!contests.length) return;
    heroIndex = Math.max(0, Math.min(Number(index), contests.length - 1));
    heroSlideStartedAt = Date.now();
    renderHero();
}

function renderContestList() {
    const contests = filteredContests();
    const grid = document.getElementById('contests-grid');
    const listTitle = document.getElementById('list-title');
    const subtitle = document.getElementById('list-subtitle');
    const titles = { '': 'Contest List', active: 'Live Contests', upcoming: 'Upcoming Contests', ended: 'Past Contests' };

    listTitle.textContent = savedOnly ? 'Saved Contests' : (titles[activeStatus] || 'Contest List');
    subtitle.textContent = `${contests.length} visible from ${allContests.length} loaded contests`;

    if (!contests.length) {
        grid.innerHTML = '<div class="empty-state"><div><strong>No contests found</strong><span>Try changing filters or search terms.</span></div></div>';
        return;
    }

    const saved = savedIds();
    grid.innerHTML = contests.map(contest => renderContestCard(contest, saved.includes(String(contest.id)))).join('');
}

function renderContestCard(contest, isSaved) {
    const platform = inferPlatform(contest);
    const href = contestHref(contest);
    const demoClick = contest.source === 'demo' ? ` onclick="event.preventDefault();openContestModal('${escapeJs(contest.id)}')"` : '';
    const start = new Date(contest.start_time);
    const end = new Date(contest.end_time);
    const target = contest.status === 'active' ? end : start;
    const durationMin = Math.max(0, Math.round((end - start) / 60000));
    const duration = durationMin >= 60 ? `${Math.round(durationMin / 60)}h` : `${durationMin}m`;
    const statusCls = { active: 'chip-active', upcoming: 'chip-upcoming', ended: 'chip-ended' }[contest.status] || 'chip-ended';
    const statusLabel = { active: 'Live', upcoming: 'Upcoming', ended: 'Past' }[contest.status] || contest.status;
    const primary = contest.status !== 'ended'
        ? `<a class="card-button primary" href="${href}"${demoClick}>${contest.status === 'active' ? 'Enter' : 'Register'}</a>`
        : `<a class="card-button" href="${href}"${demoClick}>Results</a>`;

    return `
        <article class="contest-card ${contest.status === 'active' ? 'is-live' : ''}" data-id="${escapeHtml(contest.id)}">
            <div class="platform-icon ${platform.cls}">${platform.short}</div>
            <div>
                <div class="contest-title-line">
                    <h3><a href="${href}"${demoClick}>${escapeHtml(contest.title)}</a></h3>
                    <span class="status-chip ${statusCls}">${statusLabel}</span>
                </div>
                <div class="contest-meta">
                    <span>${escapeHtml(platform.label)}</span>
                    <span>${formatDateTime(start)}</span>
                    <span>${duration}</span>
                    <span>${Number(contest.participant_count || 0)} participants</span>
                    ${Number(contest.is_rated) ? '<span>Rated</span>' : ''}
                </div>
            </div>
            <div class="contest-time">
                <strong>${contest.status === 'ended' ? 'Completed' : preciseCountdown(target - new Date())}</strong>
                <span>${contest.status === 'active' ? 'ends' : contest.status === 'upcoming' ? 'starts' : 'started'} ${relativeTime(target - new Date())}</span>
            </div>
            <div class="contest-actions">
                ${primary}
                <button class="card-button bookmark ${isSaved ? 'saved' : ''}" type="button" title="Save contest" onclick="toggleSave('${escapeJs(contest.id)}')">${isSaved ? 'Saved' : 'Save'}</button>
                ${CAN_MANAGE && contest.source !== 'demo' ? `<a class="card-button" href="/code-arena/contest_manage.php?id=${encodeURIComponent(contest.id)}">Manage</a>` : ''}
                ${IS_ADMIN && contest.source !== 'demo' && contest.status !== 'active' ? `<button class="card-button danger" type="button" onclick="openDeleteModal('${escapeJs(contest.id)}', this)">Delete</button>` : ''}
            </div>
        </article>
    `;
}

function renderFullCalendar() {
    const grid = document.getElementById('full-calendar-grid');
    const title = document.getElementById('full-calendar-title');
    const legend = document.getElementById('calendar-legend');
    if (!grid || !title || !legend) return;

    const now = new Date();
    title.textContent = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) + ' Contest Calendar';
    legend.innerHTML = Object.entries(platformColors)
        .filter(([name]) => name !== 'Code Arena')
        .map(([name, color]) => `<span class="legend-item"><span style="background:${color}"></span>${escapeHtml(name)}</span>`)
        .join('');

    const grouped = allContests.reduce((days, contest) => {
        const date = new Date(contest.start_time);
        if (Number.isNaN(date.getTime())) return days;
        const key = localDateKey(date);
        days[key] = days[key] || [];
        days[key].push(contest);
        return days;
    }, {});

    Object.keys(grouped).forEach(key => {
        grouped[key].sort((a, b) => new Date(a.start_time) - new Date(b.start_time));
    });

    const first = new Date(now.getFullYear(), now.getMonth(), 1);
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    const heads = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
        .map(day => `<div class="full-calendar-head">${day}</div>`)
        .join('');
    const blanks = Array.from({ length: first.getDay() }, () => '<div class="calendar-cell is-muted"></div>').join('');
    const cells = Array.from({ length: daysInMonth }, (_, index) => {
        const day = index + 1;
        const date = new Date(now.getFullYear(), now.getMonth(), day);
        const key = localDateKey(date);
        const contests = grouped[key] || [];
        const isToday = day === now.getDate();
        const pills = contests.slice(0, 4).map(contest => {
            const platform = inferPlatform(contest);
            const color = platformColors[platform.label] || platformColors['Code Arena'];
            return `<button class="contest-pill" type="button" style="--pill-color:${color}" title="${escapeHtml(contest.title)}" onclick="openContestModal('${escapeJs(contest.id)}')">${escapeHtml(platform.short)} ${escapeHtml(contest.title)}</button>`;
        }).join('');
        const more = contests.length > 4 ? `<button class="contest-pill more-pill" type="button" onclick="openContestModal('${escapeJs(contests[4].id)}')">+${contests.length - 4} more</button>` : '';

        return `
            <div class="calendar-cell ${isToday ? 'is-today' : ''}">
                <div class="calendar-cell-date">
                    <strong>${day}</strong>
                    ${contests.length ? `<span>${contests.length} contest${contests.length > 1 ? 's' : ''}</span>` : ''}
                </div>
                <div class="calendar-cell-events">${pills}${more}</div>
            </div>
        `;
    }).join('');

    grid.innerHTML = heads + blanks + cells;
}

function renderUtilityPanel() {
    document.getElementById('saved-toggle').classList.toggle('active', savedOnly);
    renderUtilityNextContest();
    renderUtilityInsights();
    renderUtilityHighlights();
    renderUtilityRecommendations();
}

function renderUtilityNextContest() {
    const container = document.getElementById('utility-next-contest');
    const contest = nextContest();
    if (!container) return;
    if (!contest) {
        container.innerHTML = `
            <h3 class="utility-compact-title">No contest scheduled</h3>
            <span class="utility-muted">Use this window for practice and review.</span>
        `;
        return;
    }

    const platform = inferPlatform(contest);
    const target = contest.status === 'active' ? new Date(contest.end_time) : new Date(contest.start_time);
    container.innerHTML = `
        <div class="platform-badge ${platform.cls}" style="width:34px;height:34px;border-radius:11px">${platform.short}</div>
        <h3 class="utility-compact-title">${escapeHtml(contest.title)}</h3>
        <span class="utility-muted">${escapeHtml(platform.label)} · ${formatDateTime(new Date(contest.start_time))}</span>
        <span class="utility-timer">${contest.status === 'active' ? 'Ends' : 'Starts'} ${preciseCountdown(target - new Date())}</span>
    `;
}

function renderUtilityInsights() {
    const container = document.getElementById('utility-insights');
    if (!container) return;

    const saved = savedIds().length;
    const upcoming = allContests.filter(contest => contest.status === 'upcoming').length;
    const live = allContests.filter(contest => contest.status === 'active').length;
    const platformCount = new Set(allContests.map(contest => inferPlatform(contest).label)).size;

    container.innerHTML = [
        { value: live, label: 'Live now' },
        { value: upcoming, label: 'Upcoming' },
        { value: saved, label: 'Saved by you' },
        { value: platformCount, label: 'Platforms' },
    ].map(item => `
        <div class="insight-tile">
            <strong>${item.value}</strong>
            <span>${item.label}</span>
        </div>
    `).join('');
}

function renderUtilityHighlights() {
    const container = document.getElementById('utility-highlights');
    if (!container) return;

    const highlights = heroContests().slice(0, 5);
    if (!highlights.length) {
        container.innerHTML = '<div class="widget-card"><span class="utility-muted">No upcoming highlights yet.</span></div>';
        return;
    }

    container.innerHTML = highlights.map(contest => {
        const platform = inferPlatform(contest);
        const target = contest.status === 'active' ? new Date(contest.end_time) : new Date(contest.start_time);
        return `
            <button class="highlight-item" type="button" onclick="openContestModal('${escapeJs(contest.id)}')">
                <span class="platform-badge ${platform.cls}">${platform.short}</span>
                <span class="highlight-copy">
                    <strong>${escapeHtml(contest.title)}</strong>
                    <span>${contest.status === 'active' ? 'Live' : relativeTime(target - new Date())} · ${escapeHtml(platform.label)}</span>
                </span>
            </button>
        `;
    }).join('');
}

function renderUtilityRecommendations() {
    const container = document.getElementById('utility-recommendations');
    if (!container) return;

    const upcoming = allContests.filter(contest => contest.status === 'upcoming').length;
    const saved = savedIds().length;
    const recs = [
        {
            title: upcoming ? 'Warm up before the next round' : 'Build a daily practice streak',
            body: upcoming ? 'Solve 2 easy and 1 medium problem before your next scheduled contest.' : 'No upcoming pressure. Focus on arrays, sorting, and implementation today.'
        },
        {
            title: saved ? 'Review saved contests' : 'Save contests to personalize this panel',
            body: saved ? 'Your saved list can guide reminders and focused preparation.' : 'Bookmark important contests from cards or calendar pills.'
        },
        {
            title: 'Suggested focus',
            body: 'Practice greedy, binary search, and graph traversal for common contest patterns.'
        }
    ];

    container.innerHTML = recs.map(item => `
        <div class="recommendation-item">
            <strong>${escapeHtml(item.title)}</strong>
            <span>${escapeHtml(item.body)}</span>
        </div>
    `).join('');
}

function formatDateTime(date) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function preciseCountdown(ms) {
    if (ms <= 0) return '00:00:00';
    const totalSeconds = Math.floor(ms / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':');
}

function relativeTime(ms) {
    const abs = Math.abs(ms);
    const totalMinutes = Math.ceil(abs / 60000);
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;
    const prefix = ms >= 0 ? 'in ' : '';
    const suffix = ms < 0 ? ' ago' : '';
    if (days > 0) return `${prefix}${days}d ${hours}h${suffix}`;
    if (hours > 0) return `${prefix}${hours}h ${minutes}m${suffix}`;
    return `${prefix}${minutes}m${suffix}`;
}

function toggleSave(id) {
    id = String(id);
    const saved = savedIds();
    setSavedIds(saved.includes(id) ? saved.filter(value => value !== id) : [...saved, id]);
    renderEverything();
}

function renderFocusTimer() {
    const display = document.getElementById('focus-timer-display');
    const status = document.getElementById('focus-timer-status');
    const toggle = document.getElementById('focus-timer-toggle');
    const pill = document.getElementById('focus-mode-pill');
    const workCount = document.getElementById('focus-work-count');
    const totalCount = document.getElementById('focus-total-count');
    const practiceLink = document.getElementById('focus-practice-link');
    if (!display || !status || !toggle) return;

    const minutes = Math.floor(focusRemainingSeconds / 60);
    const seconds = focusRemainingSeconds % 60;
    const modeLabel = focusMode === 'work' ? 'Work Mode' : 'Break Mode';
    display.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    toggle.textContent = focusRunning ? 'Pause' : 'Start';
    if (pill) {
        pill.textContent = modeLabel;
        pill.classList.toggle('break', focusMode === 'break');
    }
    if (workCount) workCount.textContent = focusWorkSessions;
    if (totalCount) totalCount.textContent = focusTotalSessions;
    status.textContent = focusRunning
        ? (focusMode === 'work' ? 'WORKING - deep focus active' : 'BREAK - recharge for the next round')
        : focusRemainingSeconds === modeDuration()
            ? 'Study mode ready'
            : 'Paused';
    if (practiceLink) {
        practiceLink.textContent = focusRunning
            ? 'Timer active: try DP practice problems during this focus block.'
            : 'Smart suggestion: prepare with DP, greedy, or graph practice.';
        practiceLink.href = focusMode === 'work'
            ? '/code-arena/problems.php?tag=dynamic-programming'
            : '/code-arena/problems.php?tag=implementation';
    }
}

function modeDuration() {
    return focusMode === 'work' ? FOCUS_DEFAULT_SECONDS : BREAK_DEFAULT_SECONDS;
}

function playFocusAlert() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        [0, 160, 320].forEach(delay => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = focusMode === 'work' ? 880 : 660;
            gain.gain.value = 0.08;
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + delay / 1000);
            osc.stop(ctx.currentTime + delay / 1000 + 0.12);
        });
        window.setTimeout(() => ctx.close(), 900);
    } catch (_) {}
}

function completeFocusSegment() {
    playFocusAlert();
    if (focusMode === 'work') {
        focusWorkSessions++;
        focusTotalSessions++;
        focusMode = 'break';
        focusRemainingSeconds = BREAK_DEFAULT_SECONDS;
        toast('Work session complete. Break mode started.', 'success');
    } else {
        focusTotalSessions++;
        focusMode = 'work';
        focusRemainingSeconds = FOCUS_DEFAULT_SECONDS;
        toast('Break complete. Work mode ready.', 'success');
    }
}

function toggleFocusTimer() {
    if (focusRunning) {
        window.clearInterval(focusTimerId);
        focusTimerId = null;
        focusRunning = false;
        renderFocusTimer();
        return;
    }

    if (focusRemainingSeconds <= 0) focusRemainingSeconds = modeDuration();
    focusRunning = true;
    renderFocusTimer();
    focusTimerId = window.setInterval(() => {
        focusRemainingSeconds = Math.max(0, focusRemainingSeconds - 1);
        if (focusRemainingSeconds === 0) {
            window.clearInterval(focusTimerId);
            focusTimerId = null;
            focusRunning = false;
            completeFocusSegment();
        }
        renderFocusTimer();
    }, 1000);
}

function resetFocusTimer() {
    if (focusTimerId) window.clearInterval(focusTimerId);
    focusTimerId = null;
    focusRunning = false;
    focusMode = 'work';
    focusRemainingSeconds = FOCUS_DEFAULT_SECONDS;
    renderFocusTimer();
}

function contestHref(contest) {
    return contest.source === 'demo' ? '#' : `/code-arena/contest.php?id=${encodeURIComponent(contest.id)}`;
}

function contestById(id) {
    return allContests.find(contest => String(contest.id) === String(id)) || null;
}

function escapeJs(value) {
    return String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function reminderMap() {
    try {
        return JSON.parse(localStorage.getItem(REMINDER_KEY) || '{}');
    } catch (error) {
        return {};
    }
}

function setReminderStatus(contestId) {
    const status = document.getElementById('modal-reminder-status');
    const reminders = reminderMap();
    const reminder = reminders[String(contestId)];
    if (!status) return;
    status.textContent = reminder
        ? `Reminder set ${formatDateTime(new Date(reminder.reminder_time))}`
        : 'No reminder set';
}

function openContestModal(id) {
    const contest = contestById(id);
    if (!contest) return;

    selectedContestId = String(id);
    const platform = inferPlatform(contest);
    const target = contest.status === 'active' ? new Date(contest.end_time) : new Date(contest.start_time);
    const detailsLink = document.getElementById('modal-details-link');

    document.getElementById('modal-platform').textContent = platform.label;
    document.getElementById('modal-platform').style.background = platformColors[platform.label] || platformColors['Code Arena'];
    document.getElementById('modal-title').textContent = contest.title || 'Contest details';
    document.getElementById('modal-start').textContent = formatDateTime(new Date(contest.start_time));
    document.getElementById('modal-countdown').textContent = contest.status === 'ended' ? 'Completed' : preciseCountdown(target - new Date());
    detailsLink.href = contestHref(contest);
    detailsLink.onclick = contest.source === 'demo' ? (event) => event.preventDefault() : null;
    setReminderStatus(contest.id);
    document.getElementById('contest-modal').style.display = 'flex';
}

function closeContestModal(event) {
    if (event && event.target !== document.getElementById('contest-modal')) return;
    document.getElementById('contest-modal').style.display = 'none';
}

function selectReminder(minutes, button) {
    selectedReminderMinutes = Number(minutes);
    document.querySelectorAll('.reminder-option').forEach(option => option.classList.remove('active'));
    if (button) button.classList.add('active');
}

async function saveReminder() {
    const contest = contestById(selectedContestId);
    if (!contest) return;

    const start = new Date(contest.start_time);
    const reminderTime = new Date(start.getTime() - selectedReminderMinutes * 60000);
    const reminders = reminderMap();
    reminders[String(contest.id)] = {
        contest_id: contest.id,
        reminder_time: toMysqlDateTime(reminderTime),
        status: 'pending'
    };
    localStorage.setItem(REMINDER_KEY, JSON.stringify(reminders));
    setReminderStatus(contest.id);

    try {
        await api('/code-arena/api/reminders/create.php', {
            method: 'POST',
            body: JSON.stringify({
                contest_id: contest.original_id || contest.id,
                reminder_time: toMysqlDateTime(reminderTime),
                status: 'pending'
            }),
        });
    } catch (error) {
        // Presentation mode still works locally when the reminder endpoint is unavailable.
    }

    toast('Reminder saved', 'success');
}

function showCreateForm() {
    const form = document.getElementById('create-form');
    form.style.display = 'block';
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function validDateInput(value) {
    return /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(new Date(`${value}T00:00`).getTime());
}

function validTimeInput(value) {
    return /^([01]\d|2[0-3]):[0-5]\d$/.test(value);
}

function composeContestDateTime(dateId, timeId, label) {
    const date = document.getElementById(dateId).value.trim();
    const time = document.getElementById(timeId).value.trim();
    if (!date || !time) throw new Error(`${label} date and time are required.`);
    if (!validDateInput(date)) throw new Error(`${label} date must use YYYY-MM-DD format.`);
    if (!validTimeInput(time)) throw new Error(`${label} time must use HH:MM 24-hour format.`);
    return `${date}T${time}`;
}

async function createContest() {
    const msg = document.getElementById('ct-msg');
    msg.textContent = '';
    msg.style.color = 'var(--ca-muted)';
    const title = document.getElementById('ct-title').value.trim();
    if (!title) {
        msg.textContent = 'Contest title is required.';
        msg.style.color = 'var(--ca-red)';
        return;
    }

    let startTime;
    let endTime;
    try {
        startTime = composeContestDateTime('ct-start-date', 'ct-start-time', 'Start');
        endTime = composeContestDateTime('ct-end-date', 'ct-end-time', 'End');
        if (new Date(startTime) >= new Date(endTime)) {
            throw new Error('End date/time must be after start date/time.');
        }
    } catch (error) {
        msg.textContent = error.message;
        msg.style.color = 'var(--ca-red)';
        return;
    }

    const payload = {
        title,
        start_time: startTime,
        end_time: endTime,
        description: document.getElementById('ct-desc').value.trim(),
        problem_ids: document.getElementById('ct-problems').value.trim(),
        is_rated: 1,
    };

    const { ok, data } = await api('/code-arena/api/contests/index.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
    if (ok && data.success) {
        toast('Contest created', 'success');
        document.getElementById('create-form').style.display = 'none';
        ['ct-title','ct-start-date','ct-start-time','ct-end-date','ct-end-time','ct-problems','ct-desc'].forEach(id => {
            const field = document.getElementById(id);
            if (field) field.value = '';
        });
        loadContests(activeStatus);
    } else {
        msg.textContent = data.message || 'Failed';
        msg.style.color = 'var(--ca-red)';
    }
}

function openDeleteModal(id, button) {
    deleteTargetId = id;
    deleteTargetCard = button.closest('.contest-card');
    document.getElementById('delete-overlay').style.display = 'flex';
}

function closeDeleteModal(event) {
    if (event && event.target !== document.getElementById('delete-overlay')) return;
    document.getElementById('delete-overlay').style.display = 'none';
    deleteTargetId = null;
    deleteTargetCard = null;
}

async function confirmDelete() {
    if (!deleteTargetId) return;
    const button = document.getElementById('delete-confirm-btn');
    button.disabled = true;
    button.textContent = 'Deleting';

    const { ok, data } = await api('/code-arena/api/contests/delete.php', {
        method: 'POST',
        body: JSON.stringify({ contest_id: deleteTargetId }),
    });

    button.disabled = false;
    button.textContent = 'Delete';
    document.getElementById('delete-overlay').style.display = 'none';

    if (ok && data.success) {
        toast('Contest deleted', 'success');
        allContests = allContests.filter(contest => String(contest.id) !== String(deleteTargetId));
        if (deleteTargetCard) {
            deleteTargetCard.style.opacity = '0';
            deleteTargetCard.style.transform = 'translateX(16px)';
            setTimeout(renderEverything, 220);
        } else {
            renderEverything();
        }
    } else {
        toast(data.message || 'Deletion failed', 'error');
    }

    deleteTargetId = null;
    deleteTargetCard = null;
}

document.querySelectorAll('.status-filter').forEach(button => {
    button.addEventListener('click', () => loadContests(button.dataset.status));
});
document.querySelectorAll('.platform-filter').forEach(input => {
    input.addEventListener('change', () => {
        input.closest('.filter-row').classList.toggle('active', input.checked);
        renderEverything();
    });
});
document.getElementById('contest-search').addEventListener('input', renderEverything);
document.getElementById('saved-toggle').addEventListener('click', () => {
    savedOnly = !savedOnly;
    renderEverything();
});
document.getElementById('hero-card').addEventListener('mouseenter', () => {
    heroPaused = true;
    renderHero();
});
document.getElementById('hero-card').addEventListener('mouseleave', () => {
    heroPaused = false;
    heroSlideStartedAt = Date.now();
    renderHero();
});

renderFocusTimer();
loadContests();
setInterval(renderEverything, 1000);
setInterval(() => {
    if (!heroPaused) moveHeroSlide(1);
}, HERO_SLIDE_MS);
setInterval(() => loadContests(activeStatus), 60000);
</script>
</body>
</html>
