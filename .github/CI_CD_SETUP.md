# CI/CD Setup Guide

## GitHub Secrets Configuration

You need to add the following secrets to both repositories on GitHub:

1. Go to your repository on GitHub
2. Navigate to `Settings` → `Secrets and variables` → `Actions`
3. Click `New repository secret`
4. Add the following secrets:

### Required Secrets

#### For Backend (electionbd2026-backend):
- `SERVER_HOST`: Your server IP address (e.g., `46.202.164.156`)
- `SERVER_USERNAME`: SSH username (e.g., `root` or `webadmin`)
- `SERVER_PORT`: SSH port (default: `22`)
- `SSH_PRIVATE_KEY`: Your SSH private key (entire content of your private key file)

#### For Frontend (electionbd2026-frontend):
- `SERVER_HOST`: Your server IP address (e.g., `46.202.164.156`)
- `SERVER_USERNAME`: SSH username (e.g., `root` or `webadmin`)
- `SERVER_PORT`: SSH port (default: `22`)
- `SSH_PRIVATE_KEY`: Your SSH private key (entire content of your private key file)
- `NEXT_PUBLIC_API_URL`: Your API URL (e.g., `https://api.electionbd2026.com` or `http://46.202.164.156:8000`)

## How to Get Your SSH Private Key

### Option 1: Use Existing Key (Recommended)
If you're already connecting to your server via SSH:

```bash
# On Windows (Git Bash or WSL)
cat ~/.ssh/id_rsa
# or
cat ~/.ssh/id_ed25519

# Copy the ENTIRE output including:
# -----BEGIN OPENSSH PRIVATE KEY-----
# ... (all the content)
# -----END OPENSSH PRIVATE KEY-----
```

### Option 2: Create New Key for GitHub Actions
```bash
# Generate a new SSH key pair
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_actions_deploy

# Add the public key to your server
ssh-copy-id -i ~/.ssh/github_actions_deploy.pub your-username@your-server-ip

# Display the private key to copy to GitHub
cat ~/.ssh/github_actions_deploy
```

## Workflow Details

### Backend Pipeline (`deploy.yml`)

**Triggers:**
- Push to `main` branch
- Pull request to `main` branch

**Jobs:**

1. **Tests Job** (Runs on every push/PR):
   - Sets up PHP 8.4
   - Creates MySQL test database
   - Installs dependencies
   - Runs migrations
   - Executes PHPUnit tests

2. **Deploy Job** (Runs only on push to main, after tests pass):
   - Connects to server via SSH
   - Pulls latest code from `main` branch
   - Installs/updates Composer dependencies
   - Clears all caches
   - Runs database migrations
   - Optimizes config, routes, and views
   - Restarts queue workers (maintains news generation jobs)
   - Restarts Reverb server (maintains broadcasting)
   - Sets proper permissions

### Frontend Pipeline (`deploy.yml`)

**Triggers:**
- Push to `main` branch
- Pull request to `main` branch

**Jobs:**

1. **Build and Test Job** (Runs on every push/PR):
   - Sets up Node.js 20
   - Installs dependencies
   - Runs ESLint (if configured)
   - Builds the Next.js application

2. **Deploy Job** (Runs only on push to main, after build succeeds):
   - Connects to server via SSH
   - Pulls latest code from `main` branch
   - Installs/updates npm dependencies
   - Builds the production version
   - Restarts PM2 process (no manual restart needed!)

## Workflow Process

### Development Workflow

1. **Work on your feature branch** (`faisal` or any other branch):
   ```bash
   git checkout faisal
   # Make changes
   git add .
   git commit -m "feat: your feature"
   git push origin faisal
   ```

2. **Create Pull Request to `main`**:
   - Go to GitHub
   - Create PR from `faisal` → `main`
   - CI will run tests/build automatically
   - Review the PR

3. **Merge to `main`**:
   - Once PR is approved and tests pass
   - Merge the PR
   - **Automatic deployment will start!** 🚀

### Direct Push to Main (Alternative)

```bash
# Switch to main and pull latest
git checkout main
git pull origin main

# Merge your feature branch
git merge faisal

# Push to trigger deployment
git push origin main
```

## What Happens Automatically

### Backend Deployment ✅
- ✅ Code deployment from `main` branch
- ✅ Composer dependencies updated
- ✅ Database migrations executed
- ✅ Caches cleared and optimized
- ✅ Queue workers restarted (news generation jobs continue)
- ✅ Reverb server restarted (broadcasting maintained)
- ✅ Permissions fixed

### Frontend Deployment ✅
- ✅ Code deployment from `main` branch
- ✅ NPM dependencies updated
- ✅ Production build created
- ✅ PM2 process restarted automatically
- ✅ No manual `npm run build` needed!
- ✅ No manual `pm2 restart` needed!

## Monitoring Deployments

### On GitHub:
1. Go to your repository
2. Click `Actions` tab
3. See all workflow runs
4. Click on any run to see detailed logs

### On Server (if needed):
```bash
# SSH into server
ssh your-username@your-server-ip

# Check backend logs
cd /var/www/electionbd2026-backend
tail -f storage/logs/laravel.log

# Check queue workers
php artisan queue:work --verbose

# Check Reverb status
pm2 logs electionbd2026-reverb

# Check frontend logs
pm2 logs electionbd2026-frontend

# Check PM2 status
pm2 status
```

## Troubleshooting

### If Deployment Fails:

1. **Check GitHub Actions logs**:
   - Go to Actions tab
   - Click on failed workflow
   - Read error messages

2. **Common Issues**:

   **SSH Connection Failed**:
   - Verify `SSH_PRIVATE_KEY` is correct and complete
   - Check `SERVER_HOST` and `SERVER_PORT`
   - Ensure SSH key is added to server's `~/.ssh/authorized_keys`

   **Permission Denied**:
   - Ensure SSH user has access to `/var/www/` directories
   - May need to add user to www-data group
   - Check file ownership

   **Build Failed**:
   - Check environment variables are set correctly
   - Verify dependencies are compatible
   - Review build logs for specific errors

3. **Manual Rollback** (if needed):
   ```bash
   # On server
   cd /var/www/electionbd2026-backend
   git log  # Find previous commit
   git reset --hard <previous-commit-hash>
   
   # Restart services
   php artisan queue:restart
   pm2 restart all
   ```

## Important Notes

⚠️ **Always work on feature branches** (`faisal`, `development`, etc.) and merge to `main` only when ready for production.

⚠️ **The `main` branch triggers automatic deployment**. Any push to `main` will deploy to production!

⚠️ **Queue workers and scheduled tasks continue running** during deployment. The pipeline only restarts them to pick up new code.

⚠️ **Broadcasting (Reverb) will restart** but maintains all configurations.

✅ **You never need to manually SSH and run commands again** for deployments!

## Testing the Setup

### Test Backend Pipeline:
1. Make a small change in backend (e.g., add comment to a file)
2. Commit and push to `main`
3. Watch GitHub Actions tab
4. Verify deployment on server

### Test Frontend Pipeline:
1. Make a small change in frontend (e.g., update a text)
2. Commit and push to `main`
3. Watch GitHub Actions tab
4. Check website to see changes live

## Next Steps

After setting up secrets:
1. Commit and push the `.github/workflows/deploy.yml` files to your repositories
2. Merge your current work to `main` branch
3. Watch the magic happen! ✨
