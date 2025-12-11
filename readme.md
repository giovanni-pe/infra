curl -fsSL https://get.docker.com | sudo sh

cd /etc/netplan


network:
  version: 2
  renderer: networkd
  ethernets:
    ens33:
      dhcp4: no
      addresses:
        - 172.16.24.137/24
      gateway4: 172.16.24.2  # <--- Asegúrate de que este sea tu gateway correcto
      nameservers:
        addresses: [172.16.24.140, 8.8.8.8]
        
        # nano 01-network-manager-all.yaml 
        
        sudo netplan apply
        