module.exports = {
  apps: [{
    name: 'examapp',
    script: 'php',
    args: '-S 0.0.0.0:3000 -t /home/user/examapp',
    watch: false,
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'development'
    }
  }]
}
