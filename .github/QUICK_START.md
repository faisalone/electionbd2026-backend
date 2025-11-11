# Quick Start Guide - Activating CI/CD

## Step 1: Configure GitHub Secrets

### Backend Repository (electionbd2026-backend)

1. Go to: https://github.com/faisalone/electionbd2026-backend/settings/secrets/actions
2. Click "New repository secret" and add each of these:

| Secret Name | Value | How to Get |
|-------------|-------|------------|
| `SERVER_HOST` | Your server IP | e.g., `46.202.164.156` |
| `SERVER_USERNAME` | SSH username | Usually `root` or `webadmin` |
| `SERVER_PORT` | SSH port | Usually `22` |
| `SSH_PRIVATE_KEY` | Your SSH private key | See below ⬇️ |

### Frontend Repository (electionbd2026-frontend)

1. Go to: https://github.com/faisalone/electionbd2026-frontend/settings/secrets/actions
2. Add the same 4 secrets as backend, PLUS:

| Secret Name | Value | Example |
|-------------|-------|---------|
| `NEXT_PUBLIC_API_URL` | Your API endpoint | `http://46.202.164.156:8000/api` |

### Getting Your SSH Private Key

**On Windows (Git Bash or WSL):**
```bash
cat ~/.ssh/id_rsa
# OR
cat ~/.ssh/id_ed25519
```

**On Linux/Mac:**
```bash
cat ~/.ssh/id_rsa
# OR
cat ~/.ssh/id_ed25519
```

Copy the **ENTIRE** output including:
```
-----BEGIN OPENSSH PRIVATE KEY-----
... (all the lines)
-----END OPENSSH PRIVATE KEY-----
```

**Important:** Make sure to copy all lines without any modifications!

## Step 2: Merge to Main Branch

### Option A: Via GitHub (Recommended)

**For Backend:**
1. Go to: https://github.com/faisalone/electionbd2026-backend
2. Click "Pull requests" → "New pull request"
3. Set: `base: main` ← `compare: faisal`
4. Click "Create pull request"
5. Review changes, then click "Merge pull request"
6. ✅ Automatic deployment will start!

**For Frontend:**
1. Go to: https://github.com/faisalone/electionbd2026-frontend
2. Click "Pull requests" → "New pull request"
3. Set: `base: main` ← `compare: faisal`
4. Click "Create pull request"
5. Review changes, then click "Merge pull request"
6. ✅ Automatic deployment will start!

### Option B: Via Command Line

**For Backend:**
```bash
cd c:/Users/eilmi/Desktop/developments/electionbd2026/backend

# Switch to main and update
git checkout main
git pull origin main

# Merge faisal branch
git merge faisal

# Push to trigger deployment
git push origin main
```

**For Frontend:**
```bash
cd c:/Users/eilmi/Desktop/developments/electionbd2026/frontend

# Switch to main and update
git checkout main
git pull origin main

# Merge faisal branch
git merge faisal

# Push to trigger deployment
git push origin main
```

## Step 3: Watch the Magic Happen! 🎉

### Monitor Deployment on GitHub

**Backend:**
1. Go to: https://github.com/faisalone/electionbd2026-backend/actions
2. You'll see a workflow running
3. Click on it to see live progress
4. Wait for all checks to pass ✅

**Frontend:**
1. Go to: https://github.com/faisalone/electionbd2026-frontend/actions
2. You'll see a workflow running
3. Click on it to see live progress
4. Wait for all checks to pass ✅

### Expected Timeline
- **Tests/Build**: 2-5 minutes
- **Deployment**: 2-3 minutes
- **Total**: ~5-8 minutes

## Step 4: Verify Deployment

### Check Backend
```bash
ssh your-username@your-server-ip
cd /var/www/electionbd2026-backend
git log -1  # Should show latest commit
php artisan --version
pm2 list  # Should show reverb running
```

### Check Frontend
```bash
pm2 list  # Should show electionbd2026-frontend running
pm2 logs electionbd2026-frontend --lines 20
```

### Test the Website
1. Open your website in browser
2. Check news pages show the new date format
3. Verify everything works correctly

## Troubleshooting

### If GitHub Actions Fails

**1. Check Secrets:**
- Ensure all secrets are added correctly
- No extra spaces or missing characters
- SSH key is complete with header/footer

**2. Check Logs:**
- Click on the failed workflow
- Expand the failed step
- Read the error message

**3. Common Errors:**

**"Permission denied (publickey)"**
- SSH key is incorrect or incomplete
- Check `SSH_PRIVATE_KEY` secret
- Ensure key matches the one on server

**"Host key verification failed"**
- Add to workflow (already included in our config)

**"Permission denied" on server files**
- SSH user doesn't have access to `/var/www/`
- May need to run: `sudo chown -R $USER /var/www/electionbd2026-*`

## What Happens During Deployment?

### Backend Deployment:
1. ✅ Pulls latest code from `main`
2. ✅ Installs Composer dependencies
3. ✅ Runs database migrations
4. ✅ Clears and optimizes caches
5. ✅ Restarts queue workers (news jobs continue!)
6. ✅ Restarts Reverb (broadcasting maintained!)
7. ✅ Sets correct permissions

### Frontend Deployment:
1. ✅ Pulls latest code from `main`
2. ✅ Installs NPM dependencies
3. ✅ Builds production version
4. ✅ Restarts PM2 automatically
5. ✅ No manual commands needed!

## Future Workflow

From now on, just:

1. **Work on `faisal` branch:**
   ```bash
   git checkout faisal
   # make changes
   git add .
   git commit -m "your message"
   git push origin faisal
   ```

2. **When ready for production:**
   - Create PR from `faisal` to `main` on GitHub
   - Review changes
   - Merge PR
   - **Automatic deployment!** 🚀

**You'll never need to:**
- ❌ SSH into server manually
- ❌ Run `git pull` on server
- ❌ Run `composer install` or `npm install`
- ❌ Run `npm run build`
- ❌ Run `pm2 restart`
- ❌ Run `php artisan migrate`

**Everything is automated!** ✨

## Emergency Rollback

If something goes wrong:

```bash
ssh your-username@your-server-ip

# Backend rollback
cd /var/www/electionbd2026-backend
git log --oneline -5  # Find previous commit
git reset --hard <previous-commit-hash>
php artisan migrate:rollback  # If needed
php artisan queue:restart
pm2 restart electionbd2026-reverb

# Frontend rollback
cd /var/www/electionbd2026-frontend
git log --oneline -5  # Find previous commit
git reset --hard <previous-commit-hash>
npm run build
pm2 restart electionbd2026-frontend
```

## Need Help?

- 📚 Read: `.github/CI_CD_SETUP.md` for detailed documentation
- 🔍 Check: GitHub Actions logs for error messages
- 🛠️ Run: `.github/setup-secrets.sh` to verify your secrets
- 📝 Review: Your workflow files in `.github/workflows/`

---

**Ready?** Start with Step 1: Configure GitHub Secrets! 🚀
