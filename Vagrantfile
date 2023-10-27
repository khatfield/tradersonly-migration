# -*- mode: ruby -*-
# vi: set ft=ruby :

### Project Details ###
$ip          = "192.168.88.124"
$project     = "tomigration"
$domain      = "vpai.dev"
$xdebug_port = "15024"

### Project Directories ###
$hostname   = $project + "." + $domain
$proj_root  = "/vagrant"
$docroot    = $proj_root

### User Options ###
$user       = "vagrant"
$user_home  = "/home/" + $user

### SSL Options ###
$cert_root   = "/tmp/local-certs/"
$cert_dir    = $cert_root + $domain
$ssl_root    = "/etc/vagrant-ssl/"
$ssl_dir     = $ssl_root + $domain + "/"
$cert_file   = "cert.pem"
$key_file    = "privkey.pem"
$chain_file  = "chain.pem"

### DB Options
$db_name    = $project + "_local"
$db_user    = $db_name
$db_pass    = "Sl3dg3h@mm3r"
$db_save    = $user_home + "/sql/" + $db_name + ".sql"

Vagrant.configure("2") do |config|

  config.vm.box = "bento/ubuntu-22.04"
  config.vm.disk :disk, size: "10GB", primary: true

  config.vm.provider "virtualbox" do |vb|
    vb.customize ["modifyvm", :id, "--audio", "none"]
  end

  config.trigger.after :up, :reload, :resume do |trigger|
    trigger.warn = "Updating SSL Certificates"
    trigger.run_remote = {inline: "[[ -f /usr/local/bin/update-certificates ]] && /usr/local/bin/update-certificates"}
  end

  # Networking
  config.vm.network "private_network", ip: $ip, netmask: "255.255.0.0"
  config.vm.network "public_network", bridge: "Intel(R) Ethernet Connection (17) I219-V"
  config.vm.hostname = $hostname

  # Additional Synced Folders
  config.vm.synced_folder "./.provision/ansible", "/etc/ansible"
  config.vm.synced_folder "./.provision/ssl", $cert_root
  config.vm.synced_folder "./.provision/default-files", $user_home + "/default-files"
  config.vm.synced_folder "./.provision/sql-save", $user_home + "/sql-save"
  config.vm.synced_folder "/sql/tomigration", $user_home + "/sql"

  #run the initial playbook
  config.vm.provision "ansible_local" do |ansible|
    ansible.playbook = "playbook-initial.yml"
  end

  # Ansible Configuration
  config.vm.provision "ansible_local" do |ansible|
    ansible.galaxy_role_file = "/etc/ansible/galaxy/requirements.yml"
    ansible.galaxy_roles_path = "/etc/ansible/roles/"
    ansible.playbook = "playbook.yml"
    ansible.extra_vars = {
      user_name: $user,
      project_root: $proj_root,
      app_name: $app_name,
      app_alias: $app_alias,
      app_domain: $domain,
      xdebug_port: $xdebug_port,
      cert_root: $cert_root,
      cert_dir: $cert_dir,
      ssl_root: $ssl_root,
      ssl_dir: $ssl_dir,
      db_name: $db_name,
      db_file: $db_save,
      apache_vhosts: [
        {
          servername: $hostname,
          documentroot: $docroot,
          extra_parameters: "RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L,QSA] "
        }
      ],
      apache_vhosts_ssl: [
        {
          servername: $hostname,
          documentroot: $docroot,
          certificate_file: $ssl_dir + $cert_file,
          certificate_key_file: $ssl_dir + $key_file,
          certificate_chain_file: $ssl_dir + $chain_file,
        }
      ],
      mysql_root_password: $db_pass,
      mysql_user_name: $user,
      mysql_user_password: $db_pass,
      mysql_user_home: $user_home,
      mysql_databases: [
        {name: $db_name}
      ],
      mysql_users: [{
        name: $db_user,
        host: "%",
        password: $db_pass,
        priv: "*.*:ALL"
      }]
    }
  end
end
