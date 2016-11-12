def final REPO_NAME = 'git://github.com/cakephp/cakephp-api-docs.git'
def final CAKE_REPO_NAME = 'git://github.com/cakephp/cakephp.git'
def final CHRONOS_REPO_NAME = 'git://github.com/cakephp/chronos.git'

job('API - Rebuild All API docs') {
  description('''\
  Will delete all API doc websites and rebuild them. Useful for fixing templates.
  ''')
  multiscm {
    git(REPO_NAME, 'master')
    git(CAKE_REPO_NAME, 'master')
    git(CAKE_REPO_NAME, '2.x')
    git(CAKE_REPO_NAME, '2.next')
    git(CAKE_REPO_NAME, '3.x')
    git(CAKE_REPO_NAME, '3.next')
    git(CHRONOS_REPO_NAME, 'master')
  }
  triggers {
    githubPush()
  }
  logRotator {
    daysToKeep(30)
  }
  steps {
    shell('''
rm -rf /tmp/apidocs-$GIT_COMMIT
git clone https://github.com/cakephp/cakephp-api-docs.git /tmp/apidocs-$GIT_COMMIT
cd /tmp/apidocs-$GIT_COMMIT
touch "$GIT_COMMIT" && git add "$GIT_COMMIT" && git commit -m "Regenerate for commit $GIT_COMMIT"

git remote rm origin
ssh-keyscan -t rsa 104.239.163.8 >> ~/.ssh/known_hosts
git remote | grep dokku || git remote add dokku dokku@104.239.163.8:api
git push -fv dokku master
rm -rf /tmp/apidocs-$GIT_COMMIT
    ''')
  }
}
