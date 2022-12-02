import socket
import sys
import time
import random


class Client():
    def __init__(self, port=5000):
        self.socket = socket.socket()
        self.port = port

        self.socket.connect(("localhost", port))

        # Send over the team name
        self.socket.send("Python Client".encode("utf-8"))

        # Wait to get the ready message, which includes whether we are player 1 or player 2
        # and the initial number of stones in the form of a string "{p_num} {num_stones}"
        # This is important for calculating the opponent's move in the case where we go second
        init_info = [int(x) for x in self.socket.recv(1024).decode().rstrip().split(" ")]

        self.items = init_info[:-4]
        self.team_id = init_info[-1]
        self.budget = init_info[-2]
        self.is_vikerey = init_info[-3]
        self.num_rounds = init_info[-4]
        self.all_id_budget_value = []

        print(f'Your are team {self.team_id}.')
        print(f'The starting budget is {self.budget}.')
        print(f'The value of items in auction are {self.items}.')

    def getstate(self):
        '''
        Query the server for the current state of the game and wait until a response is received
        before returning
        '''

        # Send the request
        # self.socket.send("getstate".encode("utf-8"))

        # Wait for the response (hangs here until response is received from server)
        return self.socket.recv(1024).decode().rstrip().split(" ")

    def sendbid(self, bid):
        '''
        Send a bid to the server for current auction item. The server does not send a response / acknowledgement,
        so a call to getstate() afterwards is necessary in order to wait until the next move
        '''

        self.socket.send(f"{bid}".encode("utf-8"))

    def generatebid(self, item_ind, bids):
        '''
        Given the state of the game as input, computes the desired move and returns it.
        NOTE: this is just one way to handle the agent's policy -- feel free to add other
          features or data structures as you see fit, as long as playgame() still runs!
        '''

        raise NotImplementedError

    def processresult(self, result):
        '''
        Given the state of the game as input, computes the desired move and returns it.
        NOTE: this is just one way to handle the agent's policy -- feel free to add other
          features or data structures as you see fit, as long as playgame() still runs!
        '''

        raise NotImplementedError

    def playgame(self):
        '''
        Plays through a game of Card Nim from start to finish by looping calls to getstate(),
        generatebid(), and sendbid() in that order
        '''

        for item_ind in range(len(self.items)):
            bids = []
            for j in range(self.num_rounds):
                bid = [self.generatebid(item_ind, bids), self.team_id]
                self.sendbid(" ".join([str(i) for i in bid]))
                time.sleep(0.1)
                state = self.getstate()
                if state[0] == "end":
                    sys.exit("Timeout!")
                bids = [int(x) for x in state[1:]]
            # send message about completing bid for an item
            self.socket.send("completed".encode("utf-8"))
            #  TODO: store info
            info = self.getstate()
            self.processresult(info)
            # Below is the helper to update budget and store all current teams' info
            all_id_budget_value = []
            id_budget_value = []
            for e in info[1:]:
                id_budget_value.append(int(e))
                if len(id_budget_value) == 3:
                    all_id_budget_value.append(id_budget_value)
                    id_budget_value = []
            self.all_id_budget_value = all_id_budget_value
            self.budget = self.all_id_budget_value[self.team_id-1][1]

        self.socket.close()

        # item_ind = 1
        # bid = [self.generatebid(item_ind, []), self.team_id]
        # self.sendbid(" ".join([str(i) for i in bid]))
        #
        # while True:
        #     state = self.getstate()
        #     bids = []
        #     if state[0] == "bid":
        #         bids = [int(x) for x in state[1:]]
        #     elif state[0] == "result":
        #         item_ind += 1
        #     elif state[0] == "end":
        #         break;
        #
        #     bid = [self.generatebid(item_ind, bids), self.team_id]
        #     self.sendbid(" ".join([str(i) for i in bid]))
        #
        #     time.sleep(0.1)
        #
        # self.socket.close()


class NaivePlayer(Client):
    '''
    Very simple client which just starts at the lowest possible move
    and increases its move by 1 each turn
    '''
    def __init__(self, port=5000):
        super(NaivePlayer, self).__init__(port)

    def generatebid(self, item_ind, bids):
        if len(bids) == 0:
            px = random.randint(0, self.budget//len(self.items))
            print(px)
            return px

        print(bids)
        play = random.randint(0, 1)
        if play and bids[self.team_id] <= self.budget:
            print(random.randint(bids[self.team_id], self.budget))
            return random.randint(bids[self.team_id], self.budget)
        else:
            print(bids[self.team_id])
            return bids[self.team_id]

    def processresult(self, result):
        return

class MyPlayer(Client):
    '''
    Your custom solver!
    '''

    def __init__(self, port=5000):
        super(NaivePlayer, self).__init__(port)

    def generatebid(self, state):
        '''
        TODO: put your solver logic here!
        '''
        return None


if __name__ == '__main__':
    if len(sys.argv) == 1:
        port = 5000
    else:
        port = int(sys.argv[1])

    # Change NaivePlayer(port) to MyPlayer(port) to use your custom solver
    client = NaivePlayer(port)
    client.playgame()
