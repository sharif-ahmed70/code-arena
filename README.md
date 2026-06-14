# Code Arena

Code Arena is a PHP and MySQL based online coding platform for practicing programming problems, running submissions, joining contests, tracking progress, and discussing problem-solving approaches.

## Overview

This project is built as a DBMS-style competitive programming platform. It includes user authentication, problem management, online code execution, contest workflows, leaderboard ranking, editorials, dashboards, analytics, and a discussion/help system.

## Features

- User registration and login
- Role-based access for students, instructors, and admins
- Problem browsing, filtering, bookmarking, and solving
- Online code run and submit flow with external judge integration
- Roadmap-based learning path
- Contest creation, registration, live scoreboard, and contest management
- Global leaderboard
- User profile analytics
- Student dashboard with recommendations and progress overview
- Mistake review and retry tracker
- Editorial management and solve-based editorial unlock
- Discussion system with problem-specific help threads
- Basic security hardening with session protection, security headers, rate limiting, and audit logs

## Tech Stack

- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP
- Database: MySQL
- Local Environment: XAMPP
- Editor: Monaco Editor
- Judge Provider: OnlineCompiler API
- Version Control: Git and GitHub

## Project Structure

```text
code-arena/
├── api/                    # JSON API endpoints
│   ├── auth/               # Login, register, logout
│   ├── contests/           # Contest APIs
│   ├── discuss/            # Discussion APIs
│   ├── problems/           # Problem APIs
│   ├── submissions/        # Run and submit APIs
│   └── users/              # Dashboard/profile/review APIs
├── assets/                 # CSS and JavaScript assets
├── config/                 # Database configuration
├── includes/               # Shared PHP helpers
├── *.php                   # Main application pages
├── migrations_*.sql        # Database migrations
├── seed_*.php              # Seed scripts
└── README.md
```

## Installation

1. Clone the repository:

```bash
git clone https://github.com/sharif-ahmed70/code-arena.git
```

2. Move the project into XAMPP `htdocs` if needed:

```text
C:\xampp\htdocs\code-arena
```

3. Start Apache and MySQL from XAMPP Control Panel.

4. Create the database:

```sql
CREATE DATABASE code_arena;
```

5. Import or run the required SQL/migration files from the project root.

6. Configure database credentials:

```text
config/db.php
```

7. Configure judge API key locally:

```bash
copy .env.example .env
```

Then set:

```text
ONLINE_COMPILER_API_KEY=your_api_key
```

## Usage

Open the project in your browser:

```text
http://localhost/code-arena/
```

Common workflows:

- Student: solve problems, bookmark questions, join contests, view dashboard and review mistakes
- Instructor: create/manage problems and editorials
- Admin: manage users, problems, contests, submissions, and platform data

## Screenshots

Add screenshots before portfolio submission:

```text
docs/screenshots/home.png
docs/screenshots/problems.png
docs/screenshots/contest-scoreboard.png
docs/screenshots/dashboard.png
docs/screenshots/review.png
```

## Security Notes

- Do not commit `.env` files or API keys.
- Keep database credentials local.
- Use the included migrations for rate limiting and audit logs.
- The judge API key should be provided through an environment variable.

## Author

Sharif Ahmed  
GitHub: [sharif-ahmed70](https://github.com/sharif-ahmed70)

## License

This project is prepared for academic, portfolio, and learning purposes. Add a license file if you plan to distribute it as open source.
