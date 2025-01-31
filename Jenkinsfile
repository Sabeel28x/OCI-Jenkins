pipeline {
    agent any
    stages {
        stage('Install OCI CLI (if missing)') {
            steps {
                script {
                    // Check if OCI CLI is installed
                    sh '''
                    if ! command -v oci &> /dev/null
                    then
                        echo "OCI CLI not found. Installing..."
                        bash -c "$(curl -L https://raw.githubusercontent.com/oracle/oci-cli/master/scripts/install/install.sh)"
                    else
                        echo "OCI CLI already installed."
                    fi
                    '''
                }
            }
        }
        stage('Set Path and Fetch Instance List') {
            steps {
                script {
                    // Ensure the OCI CLI path is added to the PATH and execute the command
                    sh '''
                    export PATH=$PATH:/var/lib/jenkins/bin
                    chmod +x /var/lib/jenkins/bin/oci
                    oci compute-management instance-pool list-instances \
                        --instance-pool-id ocid1.instancepool.oc1.ap-mumbai-1.aaaaaaaazi3z5myovpdtc45qensdkwuqgbyq4lkgeqkz5ish2ij7tfezg6pq \
                        --compartment-id ocid1.compartment.oc1..aaaaaaaa3s5mtrcqpxjacf53plx4cvlbw4tttytumicdcojrbe2twmmyib4q \
                        --output json
                    '''
                }
            }
        }
    }
}
